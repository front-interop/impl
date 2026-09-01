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
