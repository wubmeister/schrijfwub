<?php

namespace App\Container;

use Psr\Container\ContainerInterface;

/**
 * Service container class
 */
class Container implements ContainerInterface
{
    /**
     * The registered factories
     * @var array
     */
    protected $factories = [];

    /**
     * The instances created by the factories
     * @var array
     */
    protected $services = [];

    /**
     * Returns a service with the specified identifier
     *
     * @param string $id The identifier of the service
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return bool TRUE if there is a factory for the service, FALSE if not
     */
    public function get($id)
    {
        if (!isset($this->services[$id])) {
            if (!isset($this->factories[$id])) {
                throw new NotFoundException("No factory found for {$id}");
            }

            $this->services[$id] = $this->factories[$id]($this);
            if (!$this->services[$id]) {
                throw new ContainerException("Error while creating service {$id} from factory");
            }
        }

        return $this->services[$id];
    }

    /**
     * Adds a factory for a service with the specified id
     *
     * @param string $id The identifier of the service
     * @param mixed $factoryOrService Either a callable, which will be treated as a factory, or an other value, which will be treated as a service instance
     * @return static $this for chaining
     */
    public function set($id, $factoryOrService)
    {
        if (is_callable($factoryOrService)) {
            $this->factories[$id] = $factoryOrService;
        } else {
            $this->services[$id] = $factoryOrService;
        }
        return $this;
    }

    /**
     * Checks if a service factory exists
     *
     * @param string $id The identifier of the service
     * @return bool TRUE if there is a factory for the service, FALSE if not
     */
    public function has($id)
    {
        return isset($this->factories[$id]);
    }
}