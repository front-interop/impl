<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use LogicException;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

#[BackupGlobals(true)]
class FrankenFrontControllerTest extends TestCase
{
    public function testTheRealHandlerServesTheRequest() : void
    {
        $franken = new class (1) extends FrankenFrontController {
            protected function handleRequest(callable $handler) : bool
            {
                $handler();
                return false;
            }
        };

        $_GET = ['name' => 'World'];
        ob_start();
        $exit = $franken->run();
        $html = (string) ob_get_clean();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Hello World!', $html);
    }

    /**
     * The real handler must carry the request status back to the worker. An
     * array in $_GET makes the request front controller report a negative
     * outcome, which run() must then report too. A success case cannot pin
     * this, because 0 is also the initial value of $lastExitCode.
     */
    public function testTheRealHandlerCarriesBackANegativeStatus() : void
    {
        $franken = new class (1) extends FrankenFrontController {
            protected function handleRequest(callable $handler) : bool
            {
                $handler();
                return false;
            }
        };

        // the inner controller logs the Throwable; keep it off stderr
        $errorLog = (string) ini_get('error_log');
        ini_set('error_log', '/dev/null');
        $_GET = ['name' => ['World']];
        ob_start();

        try {
            $exit = $franken->run();
        } finally {
            $html = (string) ob_get_clean();
            ini_set('error_log', $errorLog);
        }

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('TypeError', $html);
    }

    public function testThrowingLogDoesNotEscape() : void
    {
        $franken = new class (1) extends FrankenFrontController {
            protected function handleRequest(callable $handler) : bool
            {
                $handler();
                return false;
            }

            protected function handler() : void
            {
                throw new RuntimeException('boom');
            }

            protected function log(Throwable $e) : void
            {
                throw new LogicException('the log failed too');
            }
        };

        $this->assertSame(1, $franken->run());
    }

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
