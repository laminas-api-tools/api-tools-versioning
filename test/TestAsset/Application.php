<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Versioning\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Application
{
    /** @var EventManagerInterface|null */
    protected $events;

    /** @var ServiceLocatorInterface|null */
    protected $services;

    public function setServiceManager(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    public function setEventManager(EventManagerInterface $events)
    {
        $this->events = $events;
    }

    /** @return ServiceLocatorInterface|null */
    public function getServiceManager()
    {
        return $this->services;
    }

    /** @return EventManagerInterface|null */
    public function getEventManager()
    {
        return $this->events;
    }
}
