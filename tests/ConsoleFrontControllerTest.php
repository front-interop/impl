<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;

class ConsoleFrontControllerTest extends TestCase
{
    /**
     * @return resource
     */
    protected function memory() : mixed
    {
        $stream = fopen('php://memory', 'a+');
        assert(is_resource($stream));
        return $stream;
    }

    /**
     * @param resource $stream
     */
    protected function read(mixed $stream) : string
    {
        rewind($stream);
        return (string) stream_get_contents($stream);
    }

    public function testSuccess() : void
    {
        $stdout = $this->memory();
        $front = new ConsoleFrontController(['hello.php', 'World'], $stdout);
        $this->assertSame(0, $front->run());
        $this->assertSame("Hello World!" . PHP_EOL, $this->read($stdout));
    }

    public function testNoName() : void
    {
        $stdout = $this->memory();
        $stderr = $this->memory();
        $front = new ConsoleFrontController(['hello.php'], $stdout, $stderr);
        $this->assertSame(1, $front->run());
        $this->assertSame('', $this->read($stdout));
        $this->assertStringContainsString('enter a name', $this->read($stderr));
    }

    /**
     * A Throwable must not escape run(). With $stderr closed, the write in the
     * try throws and the catch arm cannot report through the same stream; the
     * guard stops a second TypeError from escaping, and the report falls back
     * to the log.
     */
    public function testClosedStderrFallsBackToTheLog() : void
    {
        $stderr = $this->memory();

        $front = new class (
            ['hello.php'],
            $this->memory(),
            $stderr,
        ) extends ConsoleFrontController {
            public ?Throwable $logged = null;

            protected function log(Throwable $e) : void
            {
                $this->logged = $e;
            }
        };

        fclose($stderr);
        $this->assertSame(1, $front->run());
        $this->assertInstanceOf(TypeError::class, $front->logged);
    }

    public function testClosedStdoutDoesNotEscape() : void
    {
        $stdout = $this->memory();
        $stderr = $this->memory();

        $front = new ConsoleFrontController(
            ['hello.php', 'World'],
            $stdout,
            $stderr,
        );

        fclose($stdout);
        $this->assertSame(1, $front->run());
        $this->assertStringContainsString('TypeError', $this->read($stderr));
    }

    /**
     * The log is a fallback for when stderr itself fails. If stderr took the
     * report, the log must not be used as well.
     */

    /**
     * A stream can be open and still refuse the write. fwrite() reports that
     * by returning false, not by throwing, so the fallback must test the
     * return value and not rely on a catch.
     */
    #[WithoutErrorHandler]
    public function testUnwritableStderrFallsBackToTheLog() : void
    {
        $stdout = $this->memory();
        $stderr = fopen(__FILE__, 'rb');
        assert(is_resource($stderr));

        $front = new class (
            ['hello.php', 'World'],
            $stdout,
            $stderr,
        ) extends ConsoleFrontController {
            public ?Throwable $logged = null;

            protected function log(Throwable $e) : void
            {
                $this->logged = $e;
            }
        };

        fclose($stdout);
        $this->assertSame(1, $front->run());
        $this->assertInstanceOf(TypeError::class, $front->logged);
    }

    public function testAcceptedStderrDoesNotAlsoLog() : void
    {
        $stdout = $this->memory();
        $stderr = $this->memory();

        $front = new class (
            ['hello.php', 'World'],
            $stdout,
            $stderr,
        ) extends ConsoleFrontController {
            public ?Throwable $logged = null;

            protected function log(Throwable $e) : void
            {
                $this->logged = $e;
            }
        };

        fclose($stdout);
        $this->assertSame(1, $front->run());
        $this->assertStringContainsString('TypeError', $this->read($stderr));
        $this->assertNull($front->logged);
    }

    public function testZeroIsAName() : void
    {
        $stdout = $this->memory();
        $front = new ConsoleFrontController(['hello.php', '0'], $stdout);
        $this->assertSame(0, $front->run());
        $this->assertSame("Hello 0!" . PHP_EOL, $this->read($stdout));
    }

    public function testThrowingLogDoesNotEscape() : void
    {
        $stderr = $this->memory();

        $front = new class (
            ['hello.php'],
            $this->memory(),
            $stderr,
        ) extends ConsoleFrontController {
            protected function log(Throwable $e) : void
            {
                throw new LogicException('the log failed too');
            }
        };

        fclose($stderr);
        $this->assertSame(1, $front->run());
    }

    public function testRejectsNonStreamStdout() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$stdout is not a stream.');

        /** @phpstan-ignore argument.type */
        new ConsoleFrontController([], false, $this->memory());
    }

    public function testRejectsNonStreamStderr() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$stderr is not a stream.');

        /** @phpstan-ignore argument.type */
        new ConsoleFrontController([], $this->memory(), false);
    }

    public function testRejectsNonStreamResource() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$stdout is not a stream.');

        $context = stream_context_create();
        new ConsoleFrontController([], $context, $this->memory());
    }
}
