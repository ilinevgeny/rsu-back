<?php

$router = $di->getRouter();

// Define your routes here


$router->add('/admin/aut(h|h/)', [
    'namespace'  => 'rsu\controllers\admin',
    'controller' => 'auth',
    'action'     => 'login'
]);

$router->add('/admin/:controller/:action/:params', [
    'namespace'  => 'rsu\controllers\admin',
    'controller' => 1,
    'action'     => 2,
    'params'     => 3,
]);
$router->add('/admin/auth/logou(t|t/)', [
    'namespace'  => 'rsu\controllers\admin',
    'controller' => 'auth',
    'action'     => 'logout'
]);
$router->add('/admin/statement(s|s/)', [
    'namespace'  => 'rsu\controllers\admin',
    'controller' => 'statements',
    'action'     => 'list'
]);
$router->add('/admi(n|n/)', [
    'namespace'  => 'rsu\controllers\admin',
    'controller' => 'statements',
    'action'     => 'list'
]);

$router->add('/admin/house(s|s/)', [
	'namespace'  => 'rsu\controllers\admin',
	'controller' => 'houses',
	'action'     => 'index'
]);

$router->add('/admi(n|n/)', [
	'namespace'  => 'rsu\controllers\admin',
	'controller' => 'admin',
	'action'     => 'index'
]);

$router->setDefaultNamespace('rsu\controllers');

$router->handle();
