<?php

namespace WillRy\MicroRouter;

class AppSingleton
{

    /**
     * @var App
     */
    private static App $instance;

    /**
     * Connect constructor. Private singleton
     */
    private function __construct()
    {
    }

    /**
     * Connect clone. Private singleton
     */
    private function __clone()
    {
    }

    public static function getInstance(): ?App
    {
        if (empty(self::$instance)) {
            try {
                self::$instance = new App();
            } catch (\Exception $e) {
                die($e->getMessage());
            }
        }

        return self::$instance;
    }

    public function __invoke(): ?App
    {
        return self::getInstance();
    }
}
