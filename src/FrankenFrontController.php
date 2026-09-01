<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use FrontInterop\Interface\FrontTypeAliases;
use Throwable;

/**
 * @phpstan-import-type front_exit_status_int from FrontTypeAliases
 */
class FrankenFrontController extends AFrontController
{
    protected bool $continue = true;

    protected int $requestNum = 0;

    /**
     * @var front_exit_status_int
     */
    protected int $lastExitCode = 0;

    public function __construct(protected int $requestMax = 0)
    {
    }

    /**
     * @inheritdoc
     */
    public function run() : int
    {
        try {
            return $this->serve();
        } catch (Throwable $e) {
            return $this->caught($e);
        }
    }

    /**
     * @return front_exit_status_int
     */
    protected function serve() : int
    {
        $handler = fn () => $this->handler();

        while (
            $this->continue
            && ($this->requestMax === 0 || $this->requestNum < $this->requestMax)
        ) {
            $this->requestNum ++;
            $this->continue = $this->handleRequest($handler);
            gc_collect_cycles();
        }

        return $this->lastExitCode;
    }

    protected function handleRequest(callable $handler) : bool
    {
        return frankenphp_handle_request($handler);
    }

    protected function handler() : void
    {
        /** @var string[] $_GET */
        $this->lastExitCode = new RequestFrontController($_GET)->run();
    }

    /**
     * @return front_exit_status_int
     */
    protected function error(Throwable $e) : int
    {
        // nothing here may throw; a throw would escape run() itself
        try {
            $this->log($e);
        } catch (Throwable) {
            // no channel is left to report on
        }

        return 1;
    }
}
