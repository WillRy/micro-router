<?php

namespace WillRy\MicroRouter\Router;

class RouterCollection
{
    protected \stdClass $collection;

    public function initCollection($method)
    {
        if (empty($this->collection)) {
            $this->collection = new \stdClass();
            $this->collection->{$method} = [];
        }
    }

    public function add(string $method, string $path, $className, $function, $addingMiddlewaresList = [])
    {
        $this->initCollection($method);

        $this->collection->{$method}[] = [
            'path' => $path,
            'className' => $className,
            'function' => $function,
            'middlewares' => $addingMiddlewaresList
        ];
    }

    public function filter($method)
    {
        $this->initCollection($method);

        return $this->collection->{$method};
    }

    public function all()
    {
        return $this->collection;
    }
}
