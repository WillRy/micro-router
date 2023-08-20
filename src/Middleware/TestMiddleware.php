<?php

namespace WillRy\MicroRouter\Middleware;


class TestMiddleware implements MiddlewareInterface
{
    public function handle(array $data = [])
    {
        $sessionStatus = session_status();
        if($sessionStatus !== PHP_SESSION_ACTIVE && $sessionStatus !== PHP_SESSION_DISABLED) {
            session_start();

            $_SESSION['SESSION_FROM_MIDDLEWARE'] = time();
        }

    }
}
