<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use FrontInterop\Interface\FrontController;
use Throwable;

class RequestFrontController implements FrontController
{
    /**
     * @inheritdoc
     */
    public function run() : int
    {
        try {
            /** @var string[] $_GET */
            $name = $_GET['name'] ?? null;

            if ($name) {
                http_response_code(200);
                $name = htmlspecialchars($name, encoding: 'UTF-8');
                $text = "Hello {$name}!";
            } else {
                http_response_code(422);
                $text = "Please pass '?name=' in the URL.";
            }

            echo $this->html($text);
            return 0;
        } catch (Throwable $e) {
            error_log((string) $e);
            http_response_code(500);
            header('content-type: text/plain');
            echo $e;
            return 1;
        }
    }

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
}
