<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use FrontInterop\Interface\FrontController;

use Throwable;

class ConsoleFrontController implements FrontController
{
    /**
     * @param string[] $argv
     * @param resource $stdout
     * @param resource $stderr
     */
    public function __construct(
        protected array $argv,
        protected mixed $stdout = STDOUT,
        protected mixed $stderr = STDERR,
    ) {
    }

    public function run() : int
    {
        try {
            $name = $this->argv[1] ?? null;

            if (! $name) {
                fwrite($this->stderr, "Please enter a name to say hello to." . PHP_EOL);
                return 1;
            }

            fwrite($this->stdout, "Hello {$name}!" . PHP_EOL);
            return 0;
        } catch (Throwable $e) {
            error_log((string) $e);
            return 1;
        }
    }
}
