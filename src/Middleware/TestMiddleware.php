<?php

namespace WillRy\MicroRouter\Middleware;

use WillRy\MicroRouter\Router\MiddlewareInterface;

class TestMiddleware implements MiddlewareInterface
{
    public function handle(array $data = [])
    {
        $rand = rand(0, 10) % 2 === 0;
        if (!$rand) {
            echo 'TestMiddleware - Not authenticated';
            die;
        }

    }
}
