<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use FrontInterop\Interface\FrontTypeAliases;
use Throwable;

/**
 * @phpstan-import-type front_exit_status_int from FrontTypeAliases
 */
class ConsoleFrontController extends AFrontController
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
}
