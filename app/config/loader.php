<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->serviceDir
    ]
)->register();

$loader->registerNamespaces([
    'rsu\controllers' => APP_PATH . '/controllers/',
    'rsu\models' => APP_PATH. '/models/' ,
    'rsu\service' => APP_PATH. '/service/' ,
    'rsu' => APP_PATH. '/' ,
    ])->register();
