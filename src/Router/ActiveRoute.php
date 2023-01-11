<?php

namespace WillRy\MicroRouter\Router;

class ActiveRoute
{
    protected Route $route;
    protected array $params = [];

    public function __construct(Route $route, array $params = [])
    {
        $this->route = $route;
        $this->params = $params;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getParams(): array
    {
        return $this->params;
    }

}
