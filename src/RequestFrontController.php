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
class RequestFrontController implements FrontController
{
    /**
     * @var resource
     */
    protected mixed $output;

    /**
     * @param string[] $query
     * @param null|resource $output
     */
    public function __construct(protected array $query, mixed $output = null)
    {
        $output ??= fopen('php://output', 'wb');
        $this->output = $this->validStream('output', $output);
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
     * @return front_exit_status_int
     */
    protected function hello() : int
    {
        $name = $this->query['name'] ?? null;

        if ($name !== null && $name !== '') {
            http_response_code(200);
            $name = htmlspecialchars($name, encoding: 'UTF-8');
            $text = "Hello {$name}!";
        } else {
            http_response_code(422);
            $text = "Please pass '?name=' in the URL.";
        }

        $html = <<<HTML
            <html>
                <head>
                    <title>Example Front Controller</title>
                </head>
                <body>
                    <p>{$text}</p>
                </body>
            </html>
            HTML;

        fwrite($this->output, $html);
        return 0;
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
            // the response below still reports $e
        }

        try {
            http_response_code(500);
            header('content-type: text/plain');
            fwrite($this->output, (string) $e);
        } catch (Throwable) {
            // the log above already has $e
        }

        return 1;
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
