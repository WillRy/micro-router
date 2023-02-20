<?php

namespace WillRy\MicroRouter\Router;

use WillRy\MicroRouter\Router\MiddlewareInterface;

class Route
{
    protected string $method;
    protected string $path;
    protected string $className;
    protected string $function;
    protected array $middlewares;
    protected string|null $name;


    public function create(
        string $method,
        string $path,
               $className,
               $function,
        array  $middlewares = []
    )
    {
        $this->method = $method;
        $this->path = $path;
        $this->className = $className;
        $this->function = $function;
        $this->setMiddlewares($middlewares);
        $this->name = null;

        return $this;
    }

    protected function setMiddlewares(array $middlewares): void
    {
        $this->middlewares = $middlewares;
    }

    public function name(string $name)
    {
        $this->name = $name;
    }

    public function middleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
    }


    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
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
    public function getName(): string
    {
        return $this->name;
    }


}
