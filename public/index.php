<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$front = new FrontInterop\Impl\RequestFrontController($_GET);
exit($front->run());
