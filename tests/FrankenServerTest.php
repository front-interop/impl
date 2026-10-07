<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use PHPUnit\Framework\TestCase;

class FrankenServerTest extends TestCase
{
    /** @var resource */
    protected mixed $process;

    protected int $port;

    protected string $statusFile;

    protected string $workDir;

    protected function setUp() : void
    {
        $binary = $this->findBinary();
        $this->port = $this->freePort();
        $this->workDir = sys_get_temp_dir() . '/franken-' . uniqid();
        mkdir($this->workDir);
        $this->statusFile = $this->workDir . '/status';
        $root = __DIR__ . '/worker';

        $process = proc_open(
            [
                $binary,
                'php-server',
                '--no-compress',
                '--listen',
                "127.0.0.1:{$this->port}",
                '--root',
                $root,
                '--worker',
                "{$root}/index.php",
            ],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            null,
            array_merge(
                getenv(),
                [
                    'FRANKEN_STATUS_FILE' => $this->statusFile,
                    'XDG_DATA_HOME' => $this->workDir,
                    'XDG_CONFIG_HOME' => $this->workDir,
                ],
            ),
        );

        $this->assertIsResource($process);
        $this->process = $process;
        $this->waitForServer();
    }

    protected function tearDown() : void
    {
        if (! isset($this->process)) {
            return;
        }

        proc_terminate($this->process);

        for ($i = 0; $i < 50 && proc_get_status($this->process)['running']; $i ++) {
            usleep(100_000);
        }

        if (proc_get_status($this->process)['running']) {
            proc_terminate($this->process, 9);
        }

        proc_close($this->process);
        @unlink($this->statusFile);
        @rmdir($this->workDir);
    }

    public function testServesRequestsAcrossWorkerRestarts() : void
    {
        // the worker handles 3 requests, run() returns, and it restarts
        for ($i = 1; $i <= 4; $i ++) {
            [$code, $body] = $this->get("name=World{$i}");
            $this->assertSame(200, $code);
            $this->assertStringContainsString("Hello World{$i}!", $body);
        }

        $this->assertSame(['0'], $this->statuses(1));
    }

    public function testReportsANegativeStatusAndKeepsServing() : void
    {
        for ($i = 0; $i < 3; $i ++) {
            [$code, $body] = $this->get('name[]=x');
            $this->assertSame(500, $code);
            $this->assertStringContainsString('TypeError', $body);
        }

        $this->assertSame(['1'], $this->statuses(1));

        [$code, $body] = $this->get('name=After');
        $this->assertSame(200, $code);
        $this->assertStringContainsString('Hello After!', $body);
    }

    protected function findBinary() : string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('FrankenPHP does not run natively on Windows.');
        }

        $binary = getenv('FRANKENPHP_BIN') ?: $this->search('frankenphp');

        if ($binary !== null && is_executable($binary)) {
            return $binary;
        }

        if (getenv('REQUIRE_FRANKENPHP')) {
            $this->fail('frankenphp not found, and REQUIRE_FRANKENPHP is set.');
        }

        $this->markTestSkipped('frankenphp not found.');
    }

    protected function search(string $name) : ?string
    {
        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
            if (is_executable("{$dir}/{$name}")) {
                return "{$dir}/{$name}";
            }
        }

        return null;
    }

    protected function freePort() : int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertIsResource($socket);
        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }

    protected function waitForServer() : void
    {
        for ($i = 0; $i < 100; $i ++) {
            $conn = @fsockopen('127.0.0.1', $this->port);

            if ($conn !== false) {
                fclose($conn);
                return;
            }

            usleep(100_000);
        }

        $this->fail('frankenphp did not start within 10 seconds.');
    }

    /**
     * @return array{int, string}
     */
    protected function get(string $query) : array
    {
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);

        $body = (string) file_get_contents(
            "http://127.0.0.1:{$this->port}/?{$query}",
            false,
            $context,
        );

        /** @var string[] $http_response_header */

        if (preg_match('{ (\d{3}) }', $http_response_header[0], $matches) !== 1) {
            $this->fail("No status code in: {$http_response_header[0]}");
        }

        return [(int) $matches[1], $body];
    }

    /**
     * @return string[]
     */
    protected function statuses(int $count) : array
    {
        // run() returns just after the last response, so poll briefly
        for ($i = 0; $i < 50; $i ++) {
            $lines = is_file($this->statusFile)
                ? (array) file($this->statusFile, FILE_IGNORE_NEW_LINES)
                : [];

            if (count($lines) >= $count) {
                break;
            }

            usleep(100_000);
        }

        return array_map('strval', $lines);
    }
}
