<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use TypeError;

class RequestFrontControllerTest extends TestCase
{
    protected function setUp() : void
    {
        // a sentinel, so a status the front controller never sets cannot pass
        http_response_code(599);
    }

    protected function tearDown() : void
    {
        http_response_code(200);
    }

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

    /**
     * The document the front controller is expected to emit, holding $text.
     */
    protected function html(string $text) : string
    {
        return <<<HTML
            <html>
                <head>
                    <title>Example Front Controller</title>
                </head>
                <body>
                    <p>{$text}</p>
                </body>
            </html>
            HTML;
    }

    public function testSuccess() : void
    {
        $stdout = $this->memory();
        $front = new RequestFrontController(['name' => 'World'], $stdout);
        $this->assertSame(0, $front->run());
        $this->assertSame(200, http_response_code());
        $this->assertSame($this->html('Hello World!'), $this->read($stdout));
    }

    /**
     * The request was processed and a response was emitted, so this reports
     * success even though the HTTP status is 422.
     */
    public function testNoNameStillReportsSuccess() : void
    {
        $stdout = $this->memory();
        $front = new RequestFrontController([], $stdout);
        $this->assertSame(0, $front->run());
        $this->assertSame(422, http_response_code());

        $this->assertSame(
            $this->html("Please pass '?name=' in the URL."),
            $this->read($stdout),
        );
    }

    public function testEscapesName() : void
    {
        $stdout = $this->memory();
        $query = ['name' => '<script>alert("x")</script>'];
        new RequestFrontController($query, $stdout)->run();

        $this->assertSame(
            $this->html(
                'Hello &lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;!',
            ),
            $this->read($stdout),
        );
    }

    /**
     * A real query array can hold an array value, which the declared
     * `string[]` does not allow and htmlspecialchars() will not accept. The
     * resulting TypeError must not escape run().
     */
    public function testArrayNameDoesNotEscape() : void
    {
        $stdout = $this->memory();

        $query = ['name' => ['World']];

        /** @phpstan-ignore argument.type */
        $front = new class ($query, $stdout) extends RequestFrontController {
            public ?Throwable $logged = null;

            protected function log(Throwable $e) : void
            {
                $this->logged = $e;
            }
        };

        $this->assertSame(1, $front->run());
        $this->assertSame(500, http_response_code());
        $this->assertStringContainsString('TypeError', $this->read($stdout));
        $this->assertInstanceOf(TypeError::class, $front->logged);
    }

    public function testClosedStdoutDoesNotEscape() : void
    {
        $stdout = $this->memory();

        $front = new class (
            ['name' => 'World'],
            $stdout,
        ) extends RequestFrontController {
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

    public function testZeroIsAName() : void
    {
        $stdout = $this->memory();
        $front = new RequestFrontController(['name' => '0'], $stdout);
        $this->assertSame(0, $front->run());
        $this->assertSame(200, http_response_code());
        $this->assertStringContainsString('Hello 0!', $this->read($stdout));
    }

    public function testThrowingLogDoesNotEscape() : void
    {
        $front = new class ([], $this->memory()) extends RequestFrontController {
            protected function hello() : int
            {
                throw new RuntimeException('boom');
            }

            protected function log(Throwable $e) : void
            {
                throw new LogicException('the log failed too');
            }
        };

        $this->assertSame(1, $front->run());
    }

    public function testRejectsNonStreamStdout() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$stdout is not a stream.');

        /** @phpstan-ignore argument.type */
        new RequestFrontController([], false);
    }

    public function testRejectsNonStreamResource() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$stdout is not a stream.');

        $context = stream_context_create();
        new RequestFrontController([], $context);
    }
}
