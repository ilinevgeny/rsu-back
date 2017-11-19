<?php
use Phalcon\Di\FactoryDefault;
use \rsu\service\tochka\TochkaUploader;

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/../app');

error_reporting(E_ALL);
ini_set('memory_limit', '-1');
date_default_timezone_set('Europe/Moscow');

echo '[', date('H:i:s'), '] start of upload', PHP_EOL;

$di = new FactoryDefault();

include APP_PATH . '/config/router.php';
include APP_PATH . '/config/services.php';

$config = $di->getConfig();

include APP_PATH . '/config/loader.php';

$stat = new TochkaUploader();
echo $stat->getStatements();

echo '[', date('H:i:s'), '] Upload is completed', PHP_EOL;