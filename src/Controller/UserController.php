<?php

namespace WillRy\MicroRouter\Controller;

use WillRy\MicroRouter\Router\ActiveRoute;
use WillRy\MicroRouter\Router\Router;

class UserController
{
    public function index()
    {
        echo 'index';
    }

    public function createUrl()
    {
        $url = Router::route('test.route', ['id' => 1500,'id2'  => 2]);

        echo $url;
    }

    public function show(array $data)
    {
        var_dump([
            'currentRoute' => ActiveRoute::getRoute(),
            'currentRouteParams' => ActiveRoute::getParams(),
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

    public function redirect()
    {
        Router::redirect('show.user', ['id' => 1]);
    }
}
