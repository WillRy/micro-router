<?php

namespace WillRy\MicroRouter;

use WillRy\MicroRouter\Router\Router;

class App
{
    public Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * Registra uma rota GET
     * @param string $path
     * @param $className
     * @param $function
     */
    public function get(string $path, $className, $function)
    {
        $this->router->get($path, $className, $function);
    }

    /**
     * Registra uma rota POST
     * @param string $path
     * @param $className
     * @param $function
     */
    public function post(string $path, $className, $function)
    {
        $this->router->post($path, $className, $function);
    }

    /**
     * Registra uma rota PUT
     * @param string $path
     * @param $className
     * @param $function
     */
    public function put(string $path, $className, $function)
    {
        $this->router->put($path, $className, $function);
    }

    /**
     * Registra uma rota DELETE
     * @param string $path
     * @param $className
     * @param $function
     */
    public function delete(string $path, $className, $function)
    {
        $this->router->delete($path, $className, $function);
    }

    /**
     * Cria um grupo de rotas contendo um conjunto de middleware
     * @throws \Exception
     */
    public function middleware(array $middlewareList, $callback)
    {
        $this->router->setAddingMiddlewareList($middlewareList);
        $callback($this);
        $this->router->setAddingMiddlewareList([]);
    }

    /**
     * Configura a rota de 404
     * @param string $className
     * @param string $function
     * @throws \Exception
     */
    public function setNotFound(string $className, string $function)
    {
        if (!class_exists($className) || !method_exists($className, $function)) {
            throw new \Exception("Class or method doesn't exists!");
        }

        $this->router->setNotFound($className, $function);
    }

    /**
     * Configura a rota de 405
     * @param string $className
     * @param string $function
     * @throws \Exception
     */
    public function setMethodNotAllowed(string $className, string $function)
    {
        if (!class_exists($className) || !method_exists($className, $function)) {
            throw new \Exception("Class or method doesn't exists!");
        }

        $this->router->setMethodNotAllowed($className, $function);
    }

    public function getCurrentRoute()
    {
        return $this->router->getActiveRoute();
    }

    public function getRouteParams()
    {
        return $this->router->getParams();
    }

    /**
     * Executa a aplicação
     */
    public function run()
    {
        $this->router->dispatch();
    }
}
