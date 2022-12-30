<?php

namespace WillRy\MicroRouter\Router;

class RouterCollection
{
    protected static \stdClass $collection;

    /**
     * Inicializa a collection de rotas caso não exista
     * @param $method
     */
    public static function initCollection($method)
    {
        if (empty(self::$collection)) {
            self::$collection = new \stdClass();
            self::$collection->{$method} = [];
        }
    }

    /**
     * Adiciona uma rota na collection
     *
     * @param string $method
     * @param string $path
     * @param $className
     * @param $function
     * @param array $addingMiddlewaresList
     */
    public static function add(string $method, string $path, $className, $function, array $addingMiddlewaresList = [])
    {
        self::initCollection($method);

        self::$collection->{$method}[] = [
            'path' => $path,
            'className' => $className,
            'function' => $function,
            'middlewares' => $addingMiddlewaresList,
            'method' => $method
        ];
    }

    /**
     * Retorna um grupo de rotas com base no verbo HTTP
     * @param $method
     * @return mixed
     */
    public static function filter($method)
    {
        self::initCollection($method);

        return self::$collection->{$method};
    }

    /**
     * Retorna a collection de rotas
     *
     * @return \stdClass
     */
    public static function all()
    {
        return self::$collection;
    }
}
