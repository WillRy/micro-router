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

    /**
     * Registra uma rota GET
     * @param string $path
     * @param $className
     * @param $function
     * @return Route
     */
    public function get(string $path, $className, $function): Route
    {
        return $this->router->get($path, $className, $function);
    }

    /**
     * Registra uma rota POST
     * @param string $path
     * @param $className
     * @param $function
     * @return Route
     */
    public function post(string $path, $className, $function): Route
    {
        return $this->router->post($path, $className, $function);
    }

    /**
     * Registra uma rota PUT
     * @param string $path
     * @param $className
     * @param $function
     * @return Route
     */
    public function put(string $path, $className, $function): Route
    {
        return $this->router->put($path, $className, $function);
    }

    /**
     * Registra uma rota PATCH
     * @param string $path
     * @param $className
     * @param $function
     * @return Route
     */
    public function patch(string $path, $className, $function): Route
    {
        return $this->router->patch($path, $className, $function);
    }

    /**
     * Registra uma rota DELETE
     * @param string $path
     * @param $className
     * @param $function
     * @return Route
     */
    public function delete(string $path, $className, $function): Route
    {
        return $this->router->delete($path, $className, $function);
    }

    /**
     * Cria um grupo de rotas contendo um conjunto de middleware
     */
    public function middleware(array $middlewareList, $callback): void
    {
        $this->router->setAddingMiddlewareList($middlewareList);
        $callback($this);
        $this->router->setAddingMiddlewareList([]);
    }

    /**
     * Configura a rota de 404
     * @param string $className
     * @param string $function
     */
    public function setNotFound(string $className, string $function): void
    {
        $this->router->setNotFound($className, $function);
    }

    /**
     * Configura a rota de 405
     * @param string $className
     * @param string $function
     */
    public function setMethodNotAllowed(string $className, string $function): void
    {
        $this->router->setMethodNotAllowed($className, $function);
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function redirect(string $routeName, array $params = [], bool $permanent = true)
    {
        $url = $this->router->route($routeName, $params);
        header("Location: {$url}", true, $permanent ? 301 : 302);
        die;
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

            $this->router->dispatch();

        } catch (\Exception $e) {
            $class = get_class($e);
            $this->handler->render($class, $e);
        }
    }
}
