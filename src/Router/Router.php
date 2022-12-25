<?php

namespace WillRy\MicroRouter\Router;

use WillRy\MicroRouter\Middleware\MiddlewareInterface;

class Router
{
    private RouterCollection $collection;
    private string $method;
    private string $path;

    private string $notFoundClassName;
    private string $notFoundFunctionName;

    private array $addingMiddlewaresList = [];

    public function __construct(string $path, string $method)
    {
        $this->collection = new RouterCollection;
        $this->method = $method;
        $this->path = $path;
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
        $this->collection->add(
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

    public function notFound()
    {
        $function = $this->notFoundFunctionName;
        $class = $this->notFoundClassName;

        if (class_exists($class) && method_exists($class, $function)) {
            (new $class())->$function();
            die;
        }

        throw new \Exception("404", 1);
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

    public function run()
    {
        $data = $this->collection->filter($this->method);

        foreach ($data as $key => $value) {
            $result = $this->checkUrl($value['path'], $this->path);
            $callback = $value;
            if ($result['result']) {
                break;
            }
        }

        if (empty($result['result'])) {
            $callback = null;
        }

        return [
            'path' => $callback['path'] ?? null,
            'params' => $result['params'],
            'className' => $callback['className'] ?? null,
            'function' => $callback['function'] ?? null,
            'middlewares' => $callback['middlewares'] ?? [],
        ];
    }

    public function dispatch(
        string $className,
        string $functionName,
        array  $params = [],
        array  $middlewares = []
    )
    {
        foreach ($middlewares as $middleware) {
            /** @var MiddlewareInterface */
            $middleware->handle();
        }

        (new $className())->$functionName($params);
        die;
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
