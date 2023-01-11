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
            'currentRoute' => AppSingleton::getInstance()->getActiveRoute(),
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
