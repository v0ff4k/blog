<?php

namespace AppBundle\Helper;

class ServerHelper
{

    /** @var string $servHost */
    private static $servHost;

    /**
     * Get ServHost
     *
     * @return mixed
     */
    public static function getServHost()
    {
        if (!static::$servHost) {
            static::$servHost = (
                empty($_SERVER['SERVER_NAME'])
                ?  (empty($_SERVER['HTTP_HOST']) ? 'localhost' : $_SERVER['HTTP_HOST'])
                : $_SERVER['SERVER_NAME']
            );
        }

        return static::$servHost;
    }

}