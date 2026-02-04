# Front-Interop Implementation

[![PDS Skeleton](https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat-square)](https://github.com/php-pds/skeleton)
[![PDS Composer Script Names](https://img.shields.io/badge/pds-composer--script--names-blue?style=flat-square)](https://github.com/php-pds/composer-script-names)

This package include three reference implementations for Front-Interop:

- _ConsoleFrontController_
- _RequestFrontController_
- _FrankenFrontController_

These are intentionally naive to showcase the front controller functionality
itself. Real front controllers would be obtained from an IOC container, and
would include a request/response system, a router/dispatch system, a robust
error handler system, and so on.

## _ConsoleFrontController_

This is a typical command-line console front controller.

To run it, issue the following command at the package root:

```
php bin/hello.php World
```

## _RequestFrontController_

This is a typical HTTP front controller.

To run it, issue `composer hello` at the package root, then visit the following
link:

<http://localhost:8080/index.php?name=World>

## _FrankenFrontController_

This is a specialized front controller for FrankenPHP that points workers to
a _RequestFrontController_.
