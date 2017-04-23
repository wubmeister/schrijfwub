<?php

namespace App;

use App\Router\AbstractRouter;

/**
 * Abstract base class for modules
 */
abstract class Module extends AbstractRouter
{
    public function __construct($container)
    {
        $this->container = $container;
    }
}
