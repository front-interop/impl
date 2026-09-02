# front-interop/impl

[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat-square)](https://github.com/php-pds/skeleton)
[![PDS Composer Script Names](https://img.shields.io/badge/pds-composer--script--names-blue?style=flat-square)](https://github.com/php-pds/composer-script-names)

Reference implementations of the [Front-Interop][] interface for PHP 8.4+.

These implementations are intentionally naive; they exist to showcase the
[_FrontController_][] contract itself. Production front controllers would be
obtained from an IOC container and would compose a request/response system,
a router/dispatcher, and so on.

## Installation

```
composer require front-interop/impl
```

## Usage

Three reference implementations cover three execution contexts.

### _ConsoleFrontController_

A command-line front controller, reading `$argv` and writing to `$stdout` and
`$stderr`. Both streams are optional; they default to `php://stdout` and
`php://stderr`.

```php
use FrontInterop\Impl\ConsoleFrontController;

$front = new ConsoleFrontController($argv);
exit($front->run());
```

That is `bin/hello.php`, apart from the autoloader. Run it with:

```
php bin/hello.php World
```

### _RequestFrontController_

An HTTP front controller, reading an injected query array and writing HTML to
`$output`. That stream is optional; it defaults to `php://output`.

```php
use FrontInterop\Impl\RequestFrontController;

$front = new RequestFrontController($_GET);
exit($front->run());
```

That is `public/index.php`, apart from the autoloader. Run `composer hello`
at the package root, then visit:

<http://localhost:8080/index.php?name=World>

### _FrankenFrontController_

A front controller for [FrankenPHP][], wrapping a _RequestFrontController_ in
a `frankenphp_handle_request()` worker loop.

```php
use FrontInterop\Impl\FrankenFrontController;

$front = new FrankenFrontController(100);
exit($front->run());
```

It runs only under the FrankenPHP binary in worker mode, because
`frankenphp_handle_request()` does not exist elsewhere.

## Classes

| Interface         | Implementation                                                               |
| ----------------- | ---------------------------------------------------------------------------- |
| _FrontController_ | _ConsoleFrontController_, _RequestFrontController_, _FrankenFrontController_ |

All classes are in the `FrontInterop\Impl` namespace. The three concrete
classes extend _AFrontController_ for the helpers they share.

## Conformance

Three of the Front-Interop directives for `run()` shape these implementations:

- return a status between `0` and `254`
- do not terminate the process in place of returning
- do not let a _Throwable_ escape

### Returning a status

_ConsoleFrontController_ returns `1` when no name is given and `0` once it has
written its greeting.

_RequestFrontController_ returns `0` after emitting its page, and `1` from
`error()`. A `0` does not mean that nothing went wrong: it returns `0` with a
`422` when no name is given, because the request was handled and a response
was emitted. That counts as success in an HTTP context.

_FrankenFrontController_ returns the status from the last request handled,
set only by `RequestFrontController::run()`. It returns `0` if the runtime
ends the loop before any request is handled.

The `error()` method in every controller returns `1`.

### Not terminating the process

None of the implementations call `exit()` or `die()`. Their *callers* do:
`bin/hello.php` and `public/index.php` both end with `exit($front->run())`, so
the decision to end the process belongs to the code that started it.

_FrankenFrontController_ bounds its worker loop with `$requestMax` so that
`run()` returns after that many requests. A `0` maximum defers the decision to
the runtime. The loop ends when the runtime returns `false`.

### Not letting a _Throwable_ escape

Each implementation of `run()` wraps its work in a `try`/`catch` block. The
catch sends the _Throwable_ to `caught()`, which reports through `error()` and
then releases the _Throwable_. (The release is guarded because the destructor
runs at that moment, and a throw there would reach the caller after `run()`
had already computed its status.)

Each `error()` implementation guards its own writes too, for a different
cause. Stream operations there can fail: a stream can be closed, or open and
refuse the write, and a report of a failure must not itself become a second
failure.

_ConsoleFrontController_ writes to stderr and falls back to `log()` only when
that write fails. It tests what `fwrite()` returns, because a stream that
refuses the write reports `false` rather than throwing.

_RequestFrontController_ always logs, then attempts the response separately,
so a failed log does not prevent the `500`. _FrankenFrontController_ logs and
returns `1`.

The directives bind `run()` alone. _ConsoleFrontController_ and
_RequestFrontController_ throw _InvalidArgumentException_ from `__construct()`
when a stream argument is not a stream resource. Construction is outside them.

* * *

See the [Front-Interop][] interface package for the full specification.

[Front-Interop]: https://github.com/front-interop/interface
[_FrontController_]: https://github.com/front-interop/interface#frontcontroller
[FrankenPHP]: https://frankenphp.dev
