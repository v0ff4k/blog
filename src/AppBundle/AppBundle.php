<?php

namespace AppBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    /** @var null|ContainerInterface $containerInstance  */
    private static $containerInstance = null;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        self::$containerInstance = $container;
    }

    /**
     * @return ContainerInterface
     */
    public static function getContainer()
    {
        return self::$containerInstance;
    }
}
