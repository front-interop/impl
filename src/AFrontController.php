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
abstract class AFrontController implements FrontController
{
    /**
     * @inheritdoc
     */
    abstract public function run() : int;

    /**
     * Reports the caught Throwable, then releases it inside a guard; the
     * caller's variable is null afterwards. The release runs the Throwable's
     * destructor, which may throw, and a throw after run() has its status
     * would reach the caller.
     *
     * @param-out null $e
     * @return front_exit_status_int
     */
    protected function caught(Throwable &$e) : int
    {
        $status = $this->error($e);

        try {
            $e = null;

            /** @phpstan-ignore catch.neverThrown */
        } catch (Throwable) {
        }

        return $status;
    }

    /**
     * @return front_exit_status_int
     */
    abstract protected function error(Throwable $e) : int;

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

    protected function log(Throwable $e) : void
    {
        error_log((string) $e);
    }
}
