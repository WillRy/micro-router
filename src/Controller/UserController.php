<?php

namespace WillRy\MicroRouter\Controller;

use WillRy\MicroRouter\AppSingleton;

class UserController
{
    public function index()
    {
        echo 'index';
    }

    public function createUrl()
    {
        $url = AppSingleton::getInstance()->getRouter()->route('show.user', ['id' => 1]);

        echo $url;
    }

    public function show(array $data)
    {
        var_dump([
            'currentRoute' => AppSingleton::getInstance()->getActiveRoute(),
            'currentRouteParams' => AppSingleton::getInstance()->getRouteParams(),
            'controllerParams' => $data
        ]);
    }

    public function create()
    {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        var_dump($post);
    }

    public function notFound()
    {
        echo 'Pagina não encontrada';
    }

    public function methodNotAllowed()
    {
        echo 'Rota com método não permitido';
    }
}
