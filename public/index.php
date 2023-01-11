<?php

use WillRy\MicroRouter\AppSingleton;
use WillRy\MicroRouter\Controller\UserController;
use WillRy\MicroRouter\Middleware\TestMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppSingleton::getInstance();

$app->setNotFound(UserController::class, 'notFound');
$app->setMethodNotAllowed(UserController::class, 'methodNotAllowed');

$app->get('/', UserController::class, 'index')->name('home');

$app->get('/create-url', UserController::class, 'createUrl')->name('createUrl');


$app->middleware([
    new TestMiddleware()
], function ($app) {
    $app->get('/show/{id}', UserController::class, 'show')->name('show.user');
});

$app->post('/create', UserController::class, 'create')->name('create');


$app->run();
