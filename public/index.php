<?php

use WillRy\MicroRouter\AppSingleton;

require __DIR__ . '/../vendor/autoload.php';

$app = AppSingleton::getInstance();

$app->setNotFound(\WillRy\MicroRouter\Controller\UserController::class, 'notFound');
$app->setMethodNotAllowed(\WillRy\MicroRouter\Controller\UserController::class, 'methodNotAllowed');

$app->get('/', \WillRy\MicroRouter\Controller\UserController::class, 'index');


$app->middleware([
    new \WillRy\MicroRouter\Middleware\TestMiddleware()
], function ($app) {
    $app->get('/show/{id}', \WillRy\MicroRouter\Controller\UserController::class, 'show');
});

$app->post('/create', \WillRy\MicroRouter\Controller\UserController::class, 'create');

$app->run();
