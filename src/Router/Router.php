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


    public function __construct()
    {
        $this->path = $_SERVER['REQUEST_URI'] ?? '/';
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
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


    public function identifyRouteByCurrentHttpMethod(): ?array
    {
        $allRoutes = RouterCollection::filterByMethod($this->method);

        $result = $this->matches($allRoutes);

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    public function identifyRouteInDifferentHttpMethod(): ?array
    {
        $differentMethods = array_filter(RouterCollection::$methods, function ($method) {
            return $method !== $this->method;
        });

        foreach ($differentMethods as $method) {
            $allRoutes = RouterCollection::filterByMethod($method);

            $result = $this->matches($allRoutes);

            if(!empty($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     */
    public function dispatch(): void
    {

        $routeWithCorrectMethod = $this->identifyRouteByCurrentHttpMethod();

        /**
         * if not exists, necessary check if should be status 404 or 405
         */
        if (empty($routeWithCorrectMethod)) {
            $routeWithOtherMethod = $this->identifyRouteInDifferentHttpMethod();

            if (!empty($routeWithOtherMethod)) {
                $this->methodNotAllowed();
            }

            $this->notFound();
        }

        /**
         * Check if route exists
         */
        if (empty($routeWithCorrectMethod)) {
            $this->notFound();
        }

        /** @var Route $routeInfo */
        $routeInfo = $routeWithCorrectMethod[0];
        $routeParams = $routeWithCorrectMethod[1];

        $route = [
            'path' => $routeInfo->getPath() ?? null,
            'params' => $routeParams ?? [],
            'className' => $routeInfo->getClassName() ?? null,
            'function' => $routeInfo->getFunction() ?? null,
            'middlewares' => $routeInfo->getMiddlewares() ?? [],
            'method' => $routeInfo->getMethod()
        ];

        $className = $route['className'];
        $function = $route['function'];
        $params = $route['params'];
        $middlewares = $route['middlewares'];


        ActiveRoute::setRoute($routeInfo);
        ActiveRoute::setParams($routeParams);

        /** @var MiddlewareInterface $middleware */
        foreach ($middlewares as $middleware) {
            $middleware->handle();
        }

        (new $className())->$function($params);


        die;
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

    public function matches($routes)
    {
        // I used PATH_INFO instead of REQUEST_URI, because the
        // application may not be in the root direcory
        // and we dont want stuff like ?var=value
        $reqUrl = $this->path;

        $reqUrl = $reqUrl === "/" ? $reqUrl : rtrim($reqUrl, "/");

        /** @var Route $route */
        foreach ($routes as $route) {
            // convert urls like '/users/:uid/posts/:pid' to regular expression
            // $pattern = "@^" . preg_replace('/\\\:[a-zA-Z0-9\_\-]+/', '([a-zA-Z0-9\-\_]+)', preg_quote($route['url'])) . "$@D";
            $pattern = "@^" . preg_replace('/:[a-zA-Z0-9\_\-]+/', '([a-zA-Z0-9\-\_]+)', $route->getPath()) . "$@D";
            // echo $pattern."\n";
            $params = [];
            // check if the current request params the expression
            $match = preg_match($pattern, $reqUrl, $params);
            if ($match) {
                // remove the first match
                array_shift($params);
                // call the callback with the matched positions as params
                // return call_user_func_array($route['callback'], $params);
                return [$route, $params];
            }
        }
        return [];
    }

}
