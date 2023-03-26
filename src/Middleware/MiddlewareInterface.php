<?php

namespace WillRy\MicroRouter\Middleware;

interface MiddlewareInterface
{
    public function handle(array $data = []);
}
