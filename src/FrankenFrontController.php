<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use FrontInterop\Interface\FrontController;

use Throwable;

class FrankenFrontController implements FrontController
{
    protected bool $continue = true;

    protected int $requestNum = 0;

    protected int $lastExitCode = 0;

    public function __construct(
        protected int $requestMax = 0,
    ) {
    }

    public function run() : int
    {
        try {
            $handler = fn () => $this->handler();

            while (
                $this->continue
                && $this->requestMax !== 0
                && $this->requestNum < $this->requestMax
            ) {
                $this->requestNum ++;
                $this->continue = frankenphp_handle_request($handler);
                gc_collect_cycles();
            }

            return $this->lastExitCode;
        } catch (Throwable $e) {
            error_log((string) $e);
            return 1;
        }
    }

    protected function handler() : void
    {
        $this->lastExitCode = new RequestFrontController()->run();
    }
}
