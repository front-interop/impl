<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class FrankenFrontControllerTest extends TestCase
{
    public function testRequestMaxBoundsTheLoop() : void
    {
        $franken = new class (3) extends FrankenFrontController {
            public int $handled = 0;

            protected function handleRequest(callable $handler) : bool
            {
                $handler();
                return true;
            }

            protected function handler() : void
            {
                $this->handled ++;
            }
        };

        $this->assertSame(0, $franken->run());
        $this->assertSame(3, $franken->handled);
    }

    /**
     * With no $requestMax the loop is unbounded, so it ends only when the
     * runtime reports that no further request is coming.
     */
    public function testRuntimeEndingTheLoopStopsAnUnboundedRun() : void
    {
        $franken = new class (0) extends FrankenFrontController {
            public int $handled = 0;

            protected function handleRequest(callable $handler) : bool
            {
                $handler();
                return false;
            }

            protected function handler() : void
            {
                $this->handled ++;
            }
        };

        $this->assertSame(0, $franken->run());
        $this->assertSame(1, $franken->handled);
    }

    /**
     * The worker reports the exit status of the request it handled last.
     */
    public function testReportsTheLastRequestStatus() : void
    {
        $franken = new class (2) extends FrankenFrontController {
            public int $handled = 0;

            protected function handleRequest(callable $handler) : bool
            {
                $handler();
                return true;
            }

            protected function handler() : void
            {
                $this->handled ++;
                $this->lastExitCode = 3;
            }
        };

        $this->assertSame(3, $franken->run());
        $this->assertSame(2, $franken->handled);
    }

    /**
     * A Throwable raised while handling a request must not escape run().
     */
    public function testThrowingHandlerDoesNotEscape() : void
    {
        $franken = new class (1) extends FrankenFrontController {
            public ?Throwable $logged = null;

            protected function handleRequest(callable $handler) : bool
            {
                $handler();
                return true;
            }

            protected function handler() : void
            {
                throw new RuntimeException('request failed');
            }

            protected function log(Throwable $e) : void
            {
                $this->logged = $e;
            }
        };

        $this->assertSame(1, $franken->run());
        $this->assertInstanceOf(RuntimeException::class, $franken->logged);
        $this->assertSame('request failed', $franken->logged->getMessage());
    }
}
