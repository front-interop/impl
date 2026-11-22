# Change Log

## 1.0.0

First stable release.

- Require `front-interop/interface` at `^1.0`.

- Add a server test that starts a `frankenphp` binary in worker mode, sends
  real requests, and checks the status that `run()` returns. It finds the
  binary on the `PATH` or through `FRANKENPHP_BIN`, skips when there is none,
  and fails instead when `REQUIRE_FRANKENPHP` is set.

- Install FrankenPHP in CI on `ubuntu-latest` with PHP 8.4, so that the test
  runs there.

- Add a test for each controller that a `Throwable` whose destructor throws
  does not escape `run()` when `caught()` releases it.

- Respond with a fixed "Server error." from `RequestFrontController::error()`,
  leaving the details of the `Throwable` to the log, so that the response no
  longer exposes a stack trace.

- Align the README with the revised directive wording, and explain why each
  `error()` guards its writes.

- No API changes.

## 1.0.0-beta2

Conform to the revised interface directives, and add a test suite.

## 1.0.0-alpha1

Initial release.
