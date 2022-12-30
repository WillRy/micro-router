<?php

namespace WillRy\MicroRouter\Router;

use WillRy\MicroRouter\Exception\MethodNotAllowedException;
use WillRy\MicroRouter\Exception\NotFoundException;
use WillRy\MicroRouter\Middleware\MiddlewareInterface;

class Router
{
    private string $method;
    private string $path;

    private string $notFoundClassName;
    private string $notFoundFunctionName;

    private string $methodNotAllowedClassName;
    private string $methodNotAllowedFunctionName;

    private array $addingMiddlewaresList = [];

    private array $activeRoute;

    public function __construct()
    {
        $this->path = $_SERVER['REQUEST_URI'] ?? '/';
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function getActiveRoute()
    {
        return $this->activeRoute;
    }

    public function get(string $path, string $className, string $function)
    {
        $this->request('GET', $path, $className, $function);
    }

    public function post(string $path, string $className, string $function)
    {
        $this->request('POST', $path, $className, $function);
    }

    public function put(string $path, string $className, string $function)
    {
        $this->request('PUT', $path, $className, $function);
    }

    public function delete(string $path, string $className, string $function)
    {
        $this->request('DELETE', $path, $className, $function);
    }

    public function request(string $method, string $path, string $className, string $function)
    {
        RouterCollection::add(
            $method,
            $path,
            $className,
            $function,
            $this->addingMiddlewaresList
        );
    }

    public function setNotFound(string $className, string $function)
    {
        if (!class_exists($className) || !method_exists($className, $function)) {
            throw new \Exception("Class or method doesn't exists!");
        }

        $this->notFoundClassName = $className;
        $this->notFoundFunctionName = $function;
    }

    public function setMethodNotAllowed(string $className, string $function)
    {
        if (!class_exists($className) || !method_exists($className, $function)) {
            throw new \Exception("Class or method doesn't exists!");
        }

        $this->methodNotAllowedClassName = $className;
        $this->methodNotAllowedFunctionName = $function;
    }

    public function notFound()
    {
        http_response_code(404);

        $function = $this->notFoundFunctionName;
        $class = $this->notFoundClassName;

        if (class_exists($class) && method_exists($class, $function)) {
            (new $class())->$function();
            die;
        }


        throw new NotFoundException("404", 1);
    }

    public function methodNotAllowed()
    {
        http_response_code(405);

        $function = $this->methodNotAllowedFunctionName;
        $class = $this->methodNotAllowedClassName;

        if (class_exists($class) && method_exists($class, $function)) {
            (new $class())->$function();
            die;
        }


        throw new MethodNotAllowedException("405", 1);
    }

    public function setAddingMiddlewareList(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new \Exception("Middlewares should implement MiddlewareInterface");
            }
        }

        $this->addingMiddlewaresList = $middlewares;
    }



    public function identifyRoute()
    {
        $allMethods = (array) RouterCollection::all();

        $allRoutes = [];
        foreach ($allMethods as $routes) {
            $allRoutes = array_merge($allRoutes, $routes);
        }

        $matchRoutes = [];
        foreach ($allRoutes as $value) {
            $result = $this->checkUrl($value['path'], $this->path);

            if (!$result['result']) continue;

            $value['params'] = $result['params'];

            $matchRoutes[] = $value;
        }

        if (empty($matchRoutes)) {
            return null;
        }

        return $matchRoutes;
    }

    public function dispatch()
    {

        $this->activeRoute = [];

        $routes = $this->identifyRoute();

        if (empty($routes)) {
            $this->notFound();
        }

        /**
         * Filter if exists matched routes with CURRENT HTTP METHOD
         */
        $routesWithCorrectMethod = array_filter($routes, function ($route) {
            return $route['method'] === $this->method;
        });
        $routeWithCorrectMethod = reset($routesWithCorrectMethod) ?? null;

        /**
         * Filter if exists matched routes with ANOTHER HTTP METHOD
         */
        $routeWithOtherMethod = array_filter($routes, function ($route) {
            return $route['method'] !== $this->method;
        });


        /**
         * Check if route is allowed only in another HTTP METHOD
         */
        if (empty($routeWithCorrectMethod) && !empty($routeWithOtherMethod)) {
            $this->methodNotAllowed();
        }


        /**
         * Check if route exists
         */
        if (empty($routesWithCorrectMethod)) {
            $this->notFound();
        }

        $route = [
            'path' => $routeWithCorrectMethod['path'] ?? null,
            'params' => $routeWithCorrectMethod['params'] ?? [],
            'className' => $routeWithCorrectMethod['className'] ?? null,
            'function' => $routeWithCorrectMethod['function'] ?? null,
            'middlewares' => $routeWithCorrectMethod['middlewares'] ?? [],
            'method' => $routeWithCorrectMethod['method']
        ];

        $className = $route['className'];
        $function = $route['function'];
        $params = $route['params'];
        $middlewares = $route['middlewares'];


        $this->activeRoute = $route;

        /** @var MiddlewareInterface $middleware */
        foreach ($middlewares as $middleware) {
            $middleware->handle();
        }

        (new $className())->$function($params);

        die;
    }

    public function getParams()
    {
        return $this->activeRoute['params'] ?? [];
    }

    private function checkUrl(string $toFind, $subject)
    {
        preg_match_all('/\{([^\}]*)\}/', $toFind, $variables);

        $regex = str_replace('/', '\/', $toFind);

        foreach ($variables[1] as $k => $variable) {
            $as = explode(':', $variable);
            $replacement = $as[1] ?? '([a-zA-Z0-9\-\_\ ]+)';
            $regex = str_replace($variables[$k], $replacement, $regex);
        }
        $regex = preg_replace('/{([a-zA-Z]+)}/', '([a-zA-Z0-9+])', $regex);
        $result = preg_match('/^' . $regex . '$/', $subject, $params);

        return compact('result', 'params');
    }
}
