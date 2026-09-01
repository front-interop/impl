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
    protected mixed $stdout;

    /**
     * @param string[] $query
     * @param null|resource $stdout
     */
    public function __construct(protected array $query, mixed $stdout = null)
    {
        $stdout ??= fopen('php://output', 'wb');

        if (! is_resource($stdout) || get_resource_type($stdout) !== 'stream') {
            throw new InvalidArgumentException('$stdout is not a stream.');
        }

        $this->stdout = $stdout;
    }

    /**
     * @inheritdoc
     */
    public function run() : int
    {
        try {
            return $this->hello();
        } catch (Throwable $e) {
            return $this->error($e);
        }
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

        fwrite($this->stdout, $html);
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
            // no channel is left to report on
        }

        try {
            http_response_code(500);
            header('content-type: text/plain');
            fwrite($this->stdout, (string) $e);
        } catch (Throwable) {
            // the log above already has $e
        }

        return 1;
    }

    protected function log(Throwable $e) : void
    {
        error_log((string) $e);
    }
}
