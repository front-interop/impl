# front-interop/impl

[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat-square)](https://github.com/php-pds/skeleton)
[![PDS Composer Script Names](https://img.shields.io/badge/pds-composer--script--names-blue?style=flat-square)](https://github.com/php-pds/composer-script-names)

Reference implementations of the [Front-Interop][] interface for PHP 8.4+.

These implementations are intentionally naive; they exist to showcase the
[_FrontController_][] contract itself. Production front controllers would be
obtained from an IoC container and would compose a request/response system,
a router/dispatcher, and an error handler.

## Installation

```
composer require front-interop/impl
```

## Usage

Three reference implementations cover three execution contexts.

### _ConsoleFrontController_

A typical command-line console front controller, reading `$argv` and writing
to injected `$stdout`/`$stderr` resources.

```
php bin/hello.php World
```

### _RequestFrontController_

A typical HTTP front controller, reading an injected query array and writing
HTML to an injected `$output` resource.

Run `composer hello` at the package root, then visit:

<http://localhost:8080/index.php?name=World>

### _FrankenFrontController_

A specialized front controller for [FrankenPHP][], wrapping a
`RequestFrontController` in a `frankenphp_handle_request()` worker loop
bounded by an optional maximum request count (`0` for unbounded).

`ConsoleFrontController` and `RequestFrontController` throw
`InvalidArgumentException` when a stream argument is not a stream resource.
Construction happens before `run()`, and the interface directives bind
`run()` alone, so this is outside them.

## Classes

| Interface         | Implementation                                                               |
| ----------------- | ---------------------------------------------------------------------------- |
| _FrontController_ | `ConsoleFrontController`, `RequestFrontController`, `FrankenFrontController` |

All classes are in the `FrontInterop\Impl` namespace. The three concrete
classes extend `AFrontController`, which carries the error-handling helpers
they share.

## Error handling

The interface requires that `run()` return a status between `0` and `254`,
that it not terminate the process, and that it not let a `Throwable` escape.
The last of those three is what shapes the code below.

Each `run()` wraps its work in a `try` and hands anything caught to
`caught()`, which reports through `error()` and then releases the
`Throwable`. The release is guarded because the destructor runs at that
moment, and a throw there would reach the caller after `run()` had already
computed its status.

Each `error()` guards its own writes for the same reason. The handling can
fail as well: a stream can be closed, or open and refuse the write, and a
report of a failure must not become a second failure.
`ConsoleFrontController` writes to stderr and falls back to `log()` only when
that write fails, because on the command line `error_log()` writes to stderr
as well. `RequestFrontController` always logs, then attempts the response
separately, so a failed log does not prevent the `500`.

A `0` does not mean that nothing went wrong. `RequestFrontController` returns
`0` with a `422` when no name is given: the request was handled and a
response was emitted, which is success in an HTTP context.

See the [Front-Interop][] interface package for the full specification.

[Front-Interop]: https://github.com/front-interop/interface
[_FrontController_]: https://github.com/front-interop/interface#frontcontroller
[FrankenPHP]: https://frankenphp.dev
