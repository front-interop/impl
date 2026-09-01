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
        $this->stdout = $this->validStream('stdout', $stdout);

        $stderr ??= fopen('php://stderr', 'wb');
        $this->stderr = $this->validStream('stderr', $stderr);
    }

    /**
     * @inheritdoc
     */
    public function run() : int
    {
        try {
            return $this->hello();
        } catch (Throwable $e) {
            $status = $this->error($e);
            $this->release($e);
            return $status;
        }
    }

    /**
     * @return resource
     */
    protected function validStream(string $name, mixed $stream) : mixed
    {
        if (! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException("\${$name} is not a stream.");
        }

        return $stream;
    }

    /**
     * @return front_exit_status_int
     */
    protected function hello() : int
    {
        $name = $this->argv[1] ?? null;

        if ($name === null || $name === '') {
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
        // nothing here may throw; a throw would escape run() itself
        try {
            if (fwrite($this->stderr, (string) $e . PHP_EOL) !== false) {
                return 1;
            }
        } catch (Throwable) {
            // stderr failed, so fall back to the log
        }

        try {
            $this->log($e);
        } catch (Throwable) {
            // no channel is left to report on
        }

        return 1;
    }

    /**
     * Releases the caught Throwable inside a guard. Its destructor runs on
     * release and may throw, and a throw after run() has its status would
     * reach the caller.
     */
    protected function release(?Throwable &$e) : void
    {
        try {
            $e = null;

            /** @phpstan-ignore catch.neverThrown */
        } catch (Throwable) {
        }
    }

    protected function log(Throwable $e) : void
    {
        error_log((string) $e);
    }
}
