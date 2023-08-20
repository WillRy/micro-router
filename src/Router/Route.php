<?php

namespace WillRy\MicroRouter\Router;

use WillRy\MicroRouter\Middleware\MiddlewareInterface;

class Route
{
    protected string $method;
    
    protected string $path;
    
    protected string $className;
    
    protected string $function;
    
    protected array $routeOptions;

    protected array $middlewares = [];
    
    protected string $prefix;
    
    protected string $name;


    public function create(
        string $method,
        string $path,
               $className,
               $function,
        array  $routeOptions = []
    )
    {
        $this->method = $method;
       
        $this->className = $className;

        $this->function = $function;

        $this->routeOptions = $routeOptions;

        if(!empty($this->routeOptions['middlewares'])) {
            foreach($this->routeOptions['middlewares'] as $middleware) {
                $this->middleware($middleware);
            }
        }

        $this->setPath($path, $routeOptions['prefix'] ?? null);


        return $this;
    }


    public function name(string $name)
    {
        $this->name = $name;
    }

    public function middleware(string $middleware)
    {
        if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
            throw new \Exception("Middleware should extends MiddlewareInterface");
        }

        $this->middlewares[] = $middleware;
    }


    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    private function setPath(string $path, ?string $prefix)
    {
        $this->prefix = !empty($prefix) ? $this->addLeadingSlash($prefix) : '';

        $path = $this->addLeadingSlash($path);

        $this->path = !empty($this->prefix) ? "{$this->prefix}{$path}" : $path;
    }
    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function addLeadingSlash($str) {
        if (substr($str, 0, 1) !== '/') {
            $str = '/' . $str;
        }
        return $str;
    }
}
