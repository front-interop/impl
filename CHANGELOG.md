# Change Log

## NEXT

Follow the revised interface directive wording, and run FrankenPHP for real
in the tests.

- Add a server test that starts a `frankenphp` binary in worker mode, sends
  real requests, and checks the status that `run()` returns. It finds the
  binary on the `PATH` or through `FRANKENPHP_BIN`, skips when there is none,
  and fails instead when `REQUIRE_FRANKENPHP` is set.

- Install FrankenPHP in CI on `ubuntu-latest` with PHP 8.4, so that the test
  runs there.

- Align the README with the revised directive wording, and explain why each
  `error()` guards its writes.

- No API changes.

## 1.0.0-beta2

Conform to the revised interface directives, and add a test suite.

## 1.0.0-alpha1

Initial release.
