<?php

use WillRy\MicroRouter\AppSingleton;
use WillRy\MicroRouter\Controller\UserController;
use WillRy\MicroRouter\Middleware\TestMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppSingleton::getInstance();

$app->setNotFound(UserController::class, 'notFound');
$app->setMethodNotAllowed(UserController::class, 'methodNotAllowed');

$app->get('/', UserController::class, 'index', 'home');

$app->get('/create-url', UserController::class, 'createUrl', 'createUrl');


$app->middleware([
    new TestMiddleware()
], function ($app) {
    $app->get('/show/{id}', UserController::class, 'show', 'show.user');
});

$app->post('/create', UserController::class, 'create');


$app->run();
