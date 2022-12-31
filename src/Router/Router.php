<?php

namespace WillRy\MicroRouter\Router;

use WillRy\MicroRouter\Exception\MethodNotAllowedException;
use WillRy\MicroRouter\Exception\NotFoundException;
use WillRy\MicroRouter\Exception\RequiredRouteParamException;
use WillRy\MicroRouter\Exception\RouteNameNotFoundException;
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

    public function getActiveRoute(): ?array
    {
        return $this->activeRoute;
    }

    public function get(string $path, string $className, string $function, $name = null): void
    {
        $this->request('GET', $path, $className, $function, $name);
    }

    public function post(string $path, string $className, string $function, $name = null): void
    {
        $this->request('POST', $path, $className, $function, $name);
    }

    public function put(string $path, string $className, string $function, $name = null): void
    {
        $this->request('PUT', $path, $className, $function, $name);
    }

    public function delete(string $path, string $className, string $function, $name = null): void
    {
        $this->request('DELETE', $path, $className, $function, $name);
    }

    public function request(string $method, string $path, string $className, string $function, $name = null): void
    {
        RouterCollection::add(
            $method,
            $path,
            $className,
            $function,
            $this->addingMiddlewaresList,
            $name
        );
    }

    public function setNotFound(string $className, string $function): void
    {
        if (!class_exists($className) || !method_exists($className, $function)) {
            throw new \Exception("Class or method doesn't exists!");
        }

        $this->notFoundClassName = $className;
        $this->notFoundFunctionName = $function;
    }

    public function setMethodNotAllowed(string $className, string $function): void
    {
        if (!class_exists($className) || !method_exists($className, $function)) {
            throw new \Exception("Class or method doesn't exists!");
        }

        $this->methodNotAllowedClassName = $className;
        $this->methodNotAllowedFunctionName = $function;
    }

    public function notFound(): void
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

    public function methodNotAllowed(): void
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

    public function setAddingMiddlewareList(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new \Exception("Middlewares should implement MiddlewareInterface");
            }
        }

        $this->addingMiddlewaresList = $middlewares;
    }


    public function identifyRoute(): ?array
    {
        $allRoutes = RouterCollection::allRoutes();

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

    /**
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     */
    public function dispatch(): void
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

    public function getParams(): array
    {
        return $this->activeRoute['params'] ?? [];
    }

    /**
     * @param $name
     * @param array $params
     * @return string
     * @throws RequiredRouteParamException
     * @throws RouteNameNotFoundException
     */
    public function route($name, array $params = []): string
    {
        $route = RouterCollection::getRouteByName($name);

        if (empty($route)) throw new RouteNameNotFoundException("Route name not found!");

        $routeStr = $route['path'];

        preg_match_all('/{([a-zA-Z]+)}/', $routeStr, $matches);

        $diff = array_diff(array_values($matches[1]), array_keys($params));

        if (!empty($matches[1]) && $diff) {
            throw new RequiredRouteParamException("Parameters required: " . implode(',', $diff));
        }

        foreach ($params as $key => $param) {
            $routeStr = str_replace("{" . $key . "}", $param, $routeStr);
        }

        return $routeStr;
    }

    private function checkUrl(string $toFind, $subject): array
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
