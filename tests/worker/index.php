<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use FrontInterop\Impl\FrankenFrontController;

$status = new FrankenFrontController(3)->run();
$file = getenv('FRANKEN_STATUS_FILE');

if ($file !== false) {
    file_put_contents($file, $status . PHP_EOL, FILE_APPEND);
}

exit($status);
