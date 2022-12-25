<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new WillRy\MicroRouter\App();

$app->setNotFound(\WillRy\MicroRouter\Controller\UserController::class, 'notFound');

$app->get('/', \WillRy\MicroRouter\Controller\UserController::class, 'index');


$app->middleware([
    new \WillRy\MicroRouter\Middleware\TestMiddleware()
], function ($app) {
    $app->get('/show/{id}', \WillRy\MicroRouter\Controller\UserController::class, 'show');
});

$app->get('/test', \WillRy\MicroRouter\Controller\UserController::class, 'test');

$app->run();
