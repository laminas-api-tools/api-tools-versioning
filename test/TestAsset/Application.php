<?php

declare(strict_types=1);

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
