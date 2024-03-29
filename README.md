# Micro Router

Uma biblioteca de rotas simples, que permite a declaração de rotas definindo:

- Verbo HTTP
- Controller e o método responsável pela rota
- Middlewares


## Como executar a aplicação?

- Iniciar um servidor de testes para poder acessar as rotas

```shell
#iniciar servidor do php
php -S localhost:9090 -t public/
```

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

$app->get('/redirect', UserController::class, 'redirect')->name('redirect');

/**
 * Customize exception type handler
 *
 * Use for customize each exception type, like:
 * - \Exception
 * - AuthenticationException::class
 *
 *
 */
$app->handler(\Exception::class, function (\Exception $e) {
    http_response_code(500);
    var_dump('ooops');

    throw $e;
});

$app->run();

```

### Exemplo de controller

```php
<?php

use WillRy\MicroRouter\AppSingleton;
use \WillRy\MicroRouter\Router\ActiveRoute;

class UserController
{
    public function index()
    {
        echo 'index';
    }

    public function show(array $data)
    {
        var_dump([
            'currentRoute' => ActiveRoute::getRoute(),
            'currentRouteParams' => ActiveRoute::getParams(),
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

### Customização de erros

É possível customizar qualquer saída de exception

```php

/**
 * Para customizar os tipos de exceptions
 *
 * Basta registrar o tipo da exception e o callback que executa para trata-la
 * - \Exception
 * - AuthenticationException::class
 *
 *
 */
 
 
//nesse exemplo eu customizo o status code e saída das exceptions comuns
//relançando elas com o novo status code
$app->handler(\Exception::class, function (\Exception $e) {
    http_response_code(500);
    var_dump('ooops');

    //relança a exception (opcional)
    throw $e;
});

//Nesse exemplo eu customizei o status code e saída
$app->handler(AuthenticationException::class, function (\Exception $e) {
    http_response_code(401);
    var_dump('Auth Failed');
});
```
