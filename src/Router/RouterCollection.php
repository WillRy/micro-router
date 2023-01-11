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
     * @param Route $route
     * @return Route
     */
    public static function add(Route $route): Route
    {
        self::initCollection($route->getMethod());

        self::$collection->{$route->getMethod()}[] = $route;

        return $route;
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

    public static function getRouteByName(string $name): Route|null
    {
        $allRoutes = self::allRoutes();

        $route = array_values(array_filter($allRoutes, function (Route $route) use ($name) {
            return $route->getName() === $name;
        }));

        return $route[0] ?? null;
    }
}
