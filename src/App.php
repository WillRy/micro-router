<?php

namespace WillRy\MicroRouter;

use WillRy\MicroRouter\Router\ActiveRoute;
use WillRy\MicroRouter\Router\Route;
use WillRy\MicroRouter\Router\Router;

class App
{
    protected Router $router;
    protected Handler\Handler $handler;

    public function __construct()
    {
        $this->router = new Router();
        $this->handler = new Handler\Handler();
    }

    public function router(): Router
    {
        return $this->router;
    }


    public function handler(string $class, callable $callback)
    {
        $this->handler->handle($class, $callback);
    }

    /**
     * Executa a aplicação
     */
    public function run()
    {
        try {

            return $this->router->dispatch();

        } catch (\Exception $e) {
            $class = get_class($e);
            $this->handler->render($class, $e);
        }
    }
}
