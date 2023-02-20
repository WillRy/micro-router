<?php

namespace WillRy\MicroRouter\Router;

use WillRy\MicroRouter\Exception\MethodNotAllowedException;
use WillRy\MicroRouter\Exception\NotFoundException;
use WillRy\MicroRouter\Exception\RequiredRouteParamException;
use WillRy\MicroRouter\Exception\RouteNameNotFoundException;
use WillRy\MicroRouter\Router\MiddlewareInterface;

class Router
{
    private string $method;
    private string $path;

    private string $notFoundClassName;
    private string $notFoundFunctionName;

    private string $methodNotAllowedClassName;
    private string $methodNotAllowedFunctionName;

    private array $addingMiddlewaresList = [];

    private ActiveRoute|null $activeRoute;

    public function __construct()
    {
        $this->path = $_SERVER['REQUEST_URI'] ?? '/';
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function getActiveRoute(): ActiveRoute
    {
        return $this->activeRoute;
    }

    public function get(string $path, string $className, string $function): Route
    {
        return $this->request('GET', $path, $className, $function);
    }

    public function post(string $path, string $className, string $function): Route
    {
        return $this->request('POST', $path, $className, $function);
    }

    public function put(string $path, string $className, string $function): Route
    {
        return $this->request('PUT', $path, $className, $function);
    }

    public function delete(string $path, string $className, string $function): Route
    {
        return $this->request('DELETE', $path, $className, $function);
    }

    public function request(string $method, string $path, string $className, string $function): Route
    {
        $route = new Route();
        $route = $route->create($method, $path, $className, $function, $this->addingMiddlewaresList);
        RouterCollection::add($route);
        return $route;
    }

    public function setNotFound(string $className, string $function): void
    {
        $this->notFoundClassName = $className;
        $this->notFoundFunctionName = $function;
    }

    public function setMethodNotAllowed(string $className, string $function): void
    {
        $this->methodNotAllowedClassName = $className;
        $this->methodNotAllowedFunctionName = $function;
    }

    public function notFound(): void
    {
        http_response_code(404);

        $function = $this->notFoundFunctionName;
        $class = $this->notFoundClassName;

        if (!empty($function) && !empty($class)) {
            if (class_exists($class) && method_exists($class, $function)) {
                (new $class())->$function();
                die;
            }

            throw new \Exception("Class [{$class}] or method [$function] doesn't exists!");
        }


        throw new NotFoundException("404", 1);
    }

    public function methodNotAllowed(): void
    {
        http_response_code(405);

        $function = $this->methodNotAllowedFunctionName;
        $class = $this->methodNotAllowedClassName;

        if (!empty($function) && !empty($class)) {
            if (class_exists($class) && method_exists($class, $function)) {
                (new $class())->$function();
                die;
            }

            throw new \Exception("Class [{$class}] or method [$function] doesn't exists!");
        }


        throw new MethodNotAllowedException("405", 1);
    }

    public function setAddingMiddlewareList(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $this->pushMiddlewares($middleware);
        }
    }

    public function pushMiddlewares(MiddlewareInterface $middleware)
    {
        $this->addingMiddlewaresList[] = $middleware;
    }


    public function identifyRoute(): ?array
    {
        $allRoutes = RouterCollection::allRoutes();

        $matchRoutes = [];

        /** @var Route $value */
        foreach ($allRoutes as $value) {
            $result = $this->checkUrl($value->getPath(), $this->path);

            if (!$result['result']) continue;

            $matchRoutes[] = new ActiveRoute($value, $result['params']);
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

        $this->activeRoute = null;

        $routes = $this->identifyRoute();

        if (empty($routes)) {
            $this->notFound();
        }

        /**
         * Filter if exists matched routes with CURRENT HTTP METHOD
         */
        $routesWithCorrectMethod = array_filter($routes, function (ActiveRoute $route) {
            return $route->getRoute()->getMethod() === $this->method;
        });

        /** @var ActiveRoute $routeWithCorrectMethod */
        $routeWithCorrectMethod = reset($routesWithCorrectMethod) ?? null;

        /**
         * Filter if exists matched routes with ANOTHER HTTP METHOD
         */
        $routeWithOtherMethod = array_filter($routes, function (ActiveRoute $route) {
            return $route->getRoute()->getMethod() !== $this->method;
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
            'path' => $routeWithCorrectMethod->getRoute()->getPath() ?? null,
            'params' => $routeWithCorrectMethod->getParams() ?? [],
            'className' => $routeWithCorrectMethod->getRoute()->getClassName() ?? null,
            'function' => $routeWithCorrectMethod->getRoute()->getFunction() ?? null,
            'middlewares' => $routeWithCorrectMethod->getRoute()->getMiddlewares() ?? [],
            'method' => $routeWithCorrectMethod->getRoute()->getMethod()
        ];

        $className = $route['className'];
        $function = $route['function'];
        $params = $route['params'];
        $middlewares = $route['middlewares'];


        $this->activeRoute = $routeWithCorrectMethod;

        /** @var MiddlewareInterface $middleware */
        foreach ($middlewares as $middleware) {
            $middleware->handle();
        }

        (new $className())->$function($params);

        die;
    }

    public function getParams(): array
    {
        return $this->activeRoute->getParams();
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

        $routeStr = $route->getPath();

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
