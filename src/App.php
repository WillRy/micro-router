<?php

namespace WillRy\MicroRouter;

use WillRy\MicroRouter\Router\Router;

class App
{
    private Router $router;

    public function __construct()
    {
        $path_info = $_SERVER['REQUEST_URI'] ?? '/';
        $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $this->router = new Router($path_info, $request_method);
    }

    public function get(string $path, $className, $function)
    {
        $this->router->get($path, $className, $function);
    }

    public function post(string $path, $className, $function)
    {
        $this->router->post($path, $className, $function);
    }

    public function put(string $path, $className, $function)
    {
        $this->router->put($path, $className, $function);
    }

    public function delete(string $path, $className, $function)
    {
        $this->router->delete($path, $className, $function);
    }

    public function middleware(array $middlewareList, $callback)
    {
        $this->router->setAddingMiddlewareList($middlewareList);
        $callback($this);
        $this->router->setAddingMiddlewareList([]);
    }

    public function setNotFound(string $className, string $function)
    {
        if (!class_exists($className) || !method_exists($className, $function)) {
            throw new \Exception("Class or method doesn't exists!");
        }

        $this->router->setNotFound($className, $function);
    }

    public function run()
    {
        $route = $this->router->run();

        $path = $route['path'];
        $className = $route['className'];
        $function = $route['function'];
        $params = $route['params'];
        $middlewares = $route['middlewares'];

        if (empty($path)) {
            $this->router->notFound();
        }

        $this->router->dispatch(
            $className,
            $function,
            $params,
            $middlewares
        );

    }
}
