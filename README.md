# Micro Router

Uma biblioteca de rotas simples, que permite a declaração de rotas definindo:

- Verbo HTTP
- Controller e o método responsável pela rota
- Middlewares

## Exemplo de uso

**public/index.php**

```php
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


```

### Exemplo de controller

```php
<?php

use WillRy\MicroRouter\AppSingleton;

class UserController
{
    public function index()
    {
        echo 'index';
    }

    public function show(array $data)
    {
        var_dump([
            'currentRoute' => AppSingleton::getInstance()->getCurrentRoute(),
            'currentRouteParams' => AppSingleton::getInstance()->getRouteParams(),
            'controllerParams' => $data
        ]);
    }

    public function create()
    {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        var_dump($post);
    }

    public function notFound()
    {
        echo 'Pagina não encontrada';
    }

    public function methodNotAllowed()
    {
        echo 'Rota com método não permitido';
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
