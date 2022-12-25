# Micro Router

Uma biblioteca de rotas simples, que permite a declaração de rotas definindo:

- Verbo HTTP
- Controller e o método responsável pela rota
- Middlewares

## Exemplo de uso

**public/index.php**

```php
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

```

### Exemplo de controller

```php
<?php


class UserController
{
    public function index()
    {
        echo 'index';
    }

    public function show(array $data)
    {
        var_dump($data);
    }

    public function notFound()
    {
        echo 'Página não encontrada';
    }
}

```

### Exemplo de middleware

```php
<?php
use \WillRy\MicroRouter\Middleware\MiddlewareInterface;

class TestMiddleware implements MiddlewareInterface
{

    // método que executa o middleware
    public function handle(array $data = [])
    {
        $rand = rand(0, 10) % 2 === 0;
        if (!$rand) {
            echo 'Random Error';
            die;
        }

    }
}

```
