<?php

namespace WillRy\MicroRouter\Router;

class ActiveRoute
{
    protected static Route $route;
    protected static array $params = [];

    public function __construct(Route $route, array $params = [])
    {
        self::$route = $route;
        self::$params = $params;
    }

    public static function getRoute(): Route
    {
        return self::$route;
    }


    public static function getParams(): array
    {
        return self::$params;
    }


    public static function setRoute(Route $route): Route
    {
        return self::$route = $route;
    }

    public static function setParams(array $params): array
    {
        return self::$params = $params;
    }

}
