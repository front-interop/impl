# front-interop/impl

[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat-square)](https://github.com/php-pds/skeleton)
[![PDS Composer Script Names](https://img.shields.io/badge/pds-composer--script--names-blue?style=flat-square)](https://github.com/php-pds/composer-script-names)

Reference implementations of the [Front-Interop][] interface for PHP 8.4+.

These implementations are intentionally naive — they exist to showcase the
[_FrontController_][] contract itself. Production front controllers would be
obtained from an IoC container and would compose a request/response system,
a router/dispatcher, a robust error handler, and so on.

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
HTML to an injected `$stdout` resource.

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

All classes are in the `FrontInterop\Impl` namespace.

See the [Front-Interop][] interface package for the full specification.

[Front-Interop]: https://github.com/front-interop/interface
[_FrontController_]: https://github.com/front-interop/interface#frontcontroller
[FrankenPHP]: https://frankenphp.dev
