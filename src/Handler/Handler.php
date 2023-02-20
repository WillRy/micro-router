<?php

namespace WillRy\MicroRouter\Handler;

class Handler
{
    protected $handlers = [];

    public function __construct()
    {
    }

    public function handle(string $class, callable $callback)
    {
        $this->handlers[$class] = $callback;
    }

    /**
     * @throws \Exception
     */
    public function render(string $class, \Exception $e)
    {
        if(empty($this->handlers[$class]) && !empty($this->handlers[\Exception::class])) {
            $this->handlers[\Exception::class]($e);
            die;
        }

        // handler not exists
        if(empty($this->handlers[$class])) {
            throw $e;
        }



        $this->handlers[$class]($e);
        die;
    }
}
