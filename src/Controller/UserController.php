<?php

namespace WillRy\MicroRouter\Controller;

class UserController
{
    public function index()
    {
        echo 'index';
    }

    public function show(array $data)
    {
        var_dump($data);
    }

    public function test()
    {
        echo 'test';
    }

    public function notFound()
    {
        echo 'Pagina não encontrada';
    }
}
