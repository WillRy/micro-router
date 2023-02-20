<?php

namespace WillRy\MicroRouter\Router;

interface MiddlewareInterface
{
    public function handle(array $data = []);
}
