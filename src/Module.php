<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Versioning;

use Laminas\ApiTools\Versioning\AcceptListener;
use Laminas\ApiTools\Versioning\ContentTypeListener;
use Laminas\ApiTools\Versioning\VersionListener;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;

/**
 * Laminas module
 */
class Module
{
    /** @var PrototypeRouteListener */
    private $prototypeRouteListener;

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Listen to ModuleManager init event.
     *
     * Attaches a PrototypeRouteListener to the module manager event manager.
     *
     * @param ModuleManager $moduleManager
     * @return void
     */
    public function init($moduleManager)
    {
        $this->getPrototypeRouteListener()->attach($moduleManager->getEventManager());
    }

    /**
     * Listen to laminas-mvc bootstrap event.
     *
     * Attaches each of the Accept, ContentType, and Version listeners to the
     * application event manager.
     *
     * @param MvcEvent $e
     * @return void
     */
    public function onBootstrap($e)
    {
        $app      = $e->getTarget();
        $events   = $app->getEventManager();
        $services = $app->getServiceManager();
        $services->get(AcceptListener::class)->attach($events);
        $services->get(ContentTypeListener::class)->attach($events);
        $services->get(VersionListener::class)->attach($events);
    }

    /**
     * Return the prototype route listener instance.
     *
     * Lazy-instantiates an instance if none previously registered.
     *
     * @return PrototypeRouteListener
     */
    public function getPrototypeRouteListener()
    {
        if ($this->prototypeRouteListener) {
            return $this->prototypeRouteListener;
        }

        $this->prototypeRouteListener = new PrototypeRouteListener();
        return $this->prototypeRouteListener;
    }
}
