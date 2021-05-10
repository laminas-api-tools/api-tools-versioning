<?php

namespace LaminasTest\ApiTools\Versioning\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Application
{
    protected $events;
    protected $services;

    public function setServiceManager(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    public function setEventManager(EventManagerInterface $events)
    {
        $this->events = $events;
    }

    public function getServiceManager()
    {
        return $this->services;
    }

    public function getEventManager()
    {
        return $this->events;
    }
}
