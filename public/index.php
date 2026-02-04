<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$front = new FrontInterop\Impl\RequestFrontController();
exit($front->run());
