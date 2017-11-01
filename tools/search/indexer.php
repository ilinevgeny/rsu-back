<?php

use Phalcon\Di\FactoryDefault;
use rsu\Service\Search\HousesIndexer;
use rsu\Models\Houses;

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/../app');

error_reporting(E_ALL);
ini_set('memory_limit', '-1');
date_default_timezone_set('Europe/Moscow');

echo '[', date('H:i:s'), '] start of indexing', PHP_EOL;

$di = new FactoryDefault();

include APP_PATH . '/config/router.php';
include APP_PATH . '/config/services.php';

$config = $di->getConfig();

include APP_PATH . '/config/loader.php';

$offset = 0;
$limit = 100;
$housesIndexer = new HousesIndexer();
$housesIndexer->truncate();
do {
    $houses = Houses::findAll($offset, $limit);
    $offset += $limit;
} while ($housesIndexer->insertHouses($houses) != 0);

echo '[', date('H:i:s'), '] indexing is completed', PHP_EOL;