<?php

namespace WillRy\MicroRouter\Router;

class RouterCollection
{
    protected static \stdClass $collection;

    /**
     * Inicializa a collection de rotas caso não exista
     * @param $method
     */
    public static function initCollection($method): void
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
     * @param null $name
     */
    public static function add(string $method, string $path, $className, $function, array $addingMiddlewaresList = [], $name = null): void
    {
        self::initCollection($method);

        self::$collection->{$method}[] = [
            'path' => $path,
            'className' => $className,
            'function' => $function,
            'middlewares' => $addingMiddlewaresList,
            'method' => $method,
            'name' => $name
        ];
    }

    /**
     * Retorna um grupo de rotas com base no verbo HTTP
     * @param $method
     * @return array
     */
    public static function filterByMethod($method): array
    {
        self::initCollection($method);

        return self::$collection->{$method};
    }

    /**
     * Retorna a collection de rotas
     *
     * @return \stdClass
     */
    public static function all(): object
    {
        return self::$collection;
    }

    public static function allRoutes(): array
    {
        $allMethods = (array)self::$collection;

        $allRoutes = [];
        foreach ($allMethods as $routes) {
            $allRoutes = array_merge($allRoutes, $routes);
        }

        return $allRoutes;
    }

    public static function getRouteByName(string $name): ?array
    {
        $allRoutes = self::allRoutes();

        $route = array_values(array_filter($allRoutes, function ($route) use ($name) {
            return $route['name'] === $name;
        }));

        return $route[0] ?? null;
    }
}
