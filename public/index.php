<?php

use WillRy\MicroRouter\AppSingleton;
use WillRy\MicroRouter\Controller\UserController;
use WillRy\MicroRouter\Middleware\TestMiddleware;
use WillRy\MicroRouter\Router\RouterCollection;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Instância do app é um singleton, retornando sempre a mesma instância
 * e está disponível em todo o escopo da aplicação
 */
$app = AppSingleton::getInstance();

$router = $app->router();

$router->setNotFound(UserController::class, 'notFound');
$router->setMethodNotAllowed(UserController::class, 'methodNotAllowed');

$router->get('/', UserController::class, 'index')->name('home');

$router->get('/create-url', UserController::class, 'createUrl')->name('createUrl');

$router->get('/param/{id}', UserController::class, 'show')->name('show.user');
$router->get('/param/{id}/teste/{id2}', UserController::class, 'show')->name('test.route');

$router->group([
    'middlewares' => [TestMiddleware::class],
    'prefix' => 'users'
], function ($router) {
    $router->get('show/{id}', UserController::class, 'show')->name('show.user');
});

$router->post('/create', UserController::class, 'create')->name('create');

$router->get('/redirect', UserController::class, 'redirect')->name('redirect');


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
