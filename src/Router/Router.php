<?php

namespace WillRy\MicroRouter\Router;

use WillRy\MicroRouter\Exception\MethodNotAllowedException;
use WillRy\MicroRouter\Exception\NotFoundException;
use WillRy\MicroRouter\Exception\RequiredRouteParamException;
use WillRy\MicroRouter\Exception\RouteNameNotFoundException;

class Router
{
    private string $method;
    private string $path;

    private string $notFoundClassName;
    private string $notFoundFunctionName;

    private string $methodNotAllowedClassName;
    private string $methodNotAllowedFunctionName;

    private array $routeOptions = [];


    public function __construct()
    {
        $this->path = $_SERVER['REQUEST_URI'] ?? '/';
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->initializaRouteOptions();
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

    public function patch(string $path, string $className, string $function): Route
    {
        return $this->request('PATCH', $path, $className, $function);
    }

    public function delete(string $path, string $className, string $function): Route
    {
        return $this->request('DELETE', $path, $className, $function);
    }

    public function request(string $method, string $path, string $className, string $function): Route
    {
        $route = new Route();
        $route = $route->create($method, $path, $className, $function, $this->routeOptions);
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

    public function notFound()
    {
        http_response_code(404);

        $function = $this->notFoundFunctionName;
        $class = $this->notFoundClassName;

        if (!empty($function) && !empty($class)) {
            if (class_exists($class) && method_exists($class, $function)) {
                return (new $class())->$function();
            }

            throw new \Exception("Class [{$class}] or method [$function] doesn't exists!");
        }


        throw new NotFoundException("404", 1);
    }

    public function methodNotAllowed()
    {
        http_response_code(405);

        $function = $this->methodNotAllowedFunctionName;
        $class = $this->methodNotAllowedClassName;

        if (!empty($function) && !empty($class)) {
            if (class_exists($class) && method_exists($class, $function)) {
                return (new $class())->$function();
            }

            throw new \Exception("Class [{$class}] or method [$function] doesn't exists!");
        }


        throw new MethodNotAllowedException("405", 1);
    }


    public function initializaRouteOptions()
    {
        $this->routeOptions = [
            'middlewares' => [],
            'prefix' => ''
        ];
    }
    /**
     * Cria um grupo de rotas 
     **/
    public function group(array $routeOptions, callable $callback): void
    {
        $middlewares = $routeOptions['middlewares'] ?? [];
        $prefix = $routeOptions['prefix'] ?? '';

        $this->routeOptions['middlewares'] = array_merge($this->routeOptions['middlewares'], $middlewares);
        $this->routeOptions['prefix'] = $prefix;

        $callback($this);

        $this->initializaRouteOptions();
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

            if (!empty($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     */
    public function dispatch()
    {
        $routeWithCorrectMethod = $this->identifyRouteByCurrentHttpMethod();

        if (empty($routeWithCorrectMethod)) {
            $routeWithOtherMethod = $this->identifyRouteInDifferentHttpMethod();

            if($this->method == "OPTIONS") {
                return;
            } 
            
            if (!empty($routeWithOtherMethod)) {
                return $this->methodNotAllowed();
            } 
            
            return $this->notFound();
        }

        // Obtenha os detalhes da rota
        [$routeInfo, $routeParams] = $routeWithCorrectMethod;

        $route = [
            'path' => $routeInfo->getPath() ?? null,
            'params' => $routeParams ?? [],
            'className' => $routeInfo->getClassName() ?? null,
            'function' => $routeInfo->getFunction() ?? null,
            'middlewares' => $routeInfo->getMiddlewares() ?? [],
            'method' => $routeInfo->getMethod()
        ];

        // Configura a rota ativa
        ActiveRoute::setRoute($routeInfo);
        ActiveRoute::setParams($routeParams);

        // Execute os middlewares
        foreach ($route['middlewares'] as $middleware) {
            (new $middleware)->handle();
        }

        // Execute a função da rota
        $className = $route['className'];
        $function = $route['function'];
        $params = $route['params'];

        return (new $className())->$function($params);
    }

    public static function redirect(string $routeName, array $params = [], bool $permanent = true)
    {
        $url = Router::route($routeName, $params);
        header("Location: {$url}", true, $permanent ? 301 : 302);
        die;
    }

    /**
     * @param $name
     * @param array $params
     * @return string
     * @throws RequiredRouteParamException
     * @throws RouteNameNotFoundException
     */
    public static function route($name, array $params = []): string
    {
        $route = RouterCollection::getRouteByName($name);

        if (empty($route)) throw new RouteNameNotFoundException("Route name not found!");

        $routeStr = $route->getPath();

        preg_match_all('/{([a-zA-Z]+)}/', $routeStr, $matches);

        $diff = array_diff(array_values($matches[1]), array_keys($params));

        if (!empty($matches[1]) && !empty($diff)) {
            throw new RequiredRouteParamException("Parameters required: " . implode(',', $diff));
        }

        foreach ($params as $key => $param) {
            $routeStr = str_replace("{" . $key . "}", $param, $routeStr);
        }

        return $routeStr;
    }

    public function matches($routes)
    {

        $reqUrl = $this->path;

        $reqUrl = $reqUrl === "/" ? $reqUrl : rtrim($reqUrl, "/");

        /** @var Route $route */
        foreach ($routes as $route) {
            // convert urls like '/users/:uid/posts/:pid' to regular expression
            
            $pattern = '/^' . str_replace('/', '\/', $route->getPath()) . '$/';
            $pattern = preg_replace('/\{(\w+)\}/', '([a-zA-Z0-9\-\_]+)', $pattern);
            
            
            $params = [];
            // check if the current request params the expression
            $match = preg_match($pattern, $reqUrl, $params);
            if ($match) {
                // remove the first match
                $params = array_slice($params, 1);
                // call the callback with the matched positions as params
                // return call_user_func_array($route['callback'], $params);
                return [$route, $params];
            }
        }
        return [];
    }
}
