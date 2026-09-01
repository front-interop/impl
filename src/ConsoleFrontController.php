<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use FrontInterop\Interface\FrontController;
use FrontInterop\Interface\FrontTypeAliases;
use InvalidArgumentException;
use Throwable;

/**
 * @phpstan-import-type front_exit_status_int from FrontTypeAliases
 */
class ConsoleFrontController implements FrontController
{
    /**
     * @var resource
     */
    protected mixed $stdout;

    /**
     * @var resource
     */
    protected mixed $stderr;

    /**
     * @param string[] $argv
     * @param null|resource $stdout
     * @param null|resource $stderr
     */
    public function __construct(
        protected array $argv,
        mixed $stdout = null,
        mixed $stderr = null,
    ) {
        $stdout ??= fopen('php://stdout', 'wb');
        $stderr ??= fopen('php://stderr', 'wb');

        if (! is_resource($stdout) || get_resource_type($stdout) !== 'stream') {
            throw new InvalidArgumentException('$stdout is not a stream.');
        }

        if (! is_resource($stderr) || get_resource_type($stderr) !== 'stream') {
            throw new InvalidArgumentException('$stderr is not a stream.');
        }

        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    /**
     * @inheritdoc
     */
    public function run() : int
    {
        try {
            return $this->hello();
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @return front_exit_status_int
     */
    protected function hello() : int
    {
        $name = $this->argv[1] ?? null;

        if (! $name) {
            fwrite(
                $this->stderr,
                "Please enter a name to say hello to." . PHP_EOL,
            );

            return 1;
        }

        fwrite($this->stdout, "Hello {$name}!" . PHP_EOL);
        return 0;
    }

    /**
     * @return front_exit_status_int
     */
    protected function error(Throwable $e) : int
    {
        // a throw here would escape run() itself
        try {
            fwrite($this->stderr, (string) $e . PHP_EOL);
        } catch (Throwable) {
            $this->log($e);
        }

        return 1;
    }

    protected function log(Throwable $e) : void
    {
        error_log((string) $e);
    }
}
