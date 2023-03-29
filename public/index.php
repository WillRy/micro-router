<?php

use WillRy\MicroRouter\AppSingleton;
use WillRy\MicroRouter\Controller\UserController;
use WillRy\MicroRouter\Middleware\TestMiddleware;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Instância do app é um singleton, retornando sempre a mesma instância
 * e está disponível em todo o escopo da aplicação
 */
$app = AppSingleton::getInstance();

$app->setNotFound(UserController::class, 'notFound');
$app->setMethodNotAllowed(UserController::class, 'methodNotAllowed');

$app->get('/', UserController::class, 'index')->name('home');

$app->get('/create-url', UserController::class, 'createUrl')->name('createUrl');

$app->get('/param/:id', UserController::class, 'show')->name('show.user');

$app->middleware([
    new TestMiddleware()
], function ($app) {
    $app->get('/show/:id', UserController::class, 'show')->name('show.user');
});

$app->post('/create', UserController::class, 'create')->name('create');

$app->get('/redirect', UserController::class, 'redirect')->name('redirect');

/**
 * Para customizar os tipos de exceptions
 *
 * Basta registrar o tipo da exception e o callback que executa para trata-la
 * - \Exception
 * - AuthenticationException::class
 *
 *  nesse exemplo eu customizo o status code e saída das exceptions comuns
 *  relançando elas com o novo status code
 */
$app->handler(\Exception::class, function (\Exception $e) {
    http_response_code(500);
    var_dump('ooops');

    //relança a exception (opcional)
    throw $e;
});

$app->run();
