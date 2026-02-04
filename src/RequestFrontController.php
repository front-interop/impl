<?php
declare(strict_types=1);

namespace FrontInterop\Impl;

use FrontInterop\Interface\FrontController;

use Throwable;

class RequestFrontController implements FrontController
{
    public function run() : int
    {
        try {
            /** @var string[] $_GET */
            $name = $_GET['name'] ?? null;

            if ($name) {
                http_response_code(200);
                $name = htmlspecialchars($name, encoding: 'UTF-8');
                $text = "Hello {$name}!";
                $code = 0;
            } else {
                http_response_code(422);
                $text = "Please pass '?name=' in the URL.";
                $code = 1;
            }

            echo $this->html($text);
            return $code;
        } catch (Throwable $e) {
            http_response_code(500);
            error_log((string) $e);
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
