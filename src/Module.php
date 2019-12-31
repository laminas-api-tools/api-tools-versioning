<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Versioning;

/**
 * Laminas module
 */
class Module
{
    /**
     * @var PrototypeRouteListener
     */
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
     * @param \Laminas\ModuleManager\ModuleManager $moduleManager
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
     * @param \Laminas\Mvc\MvcEvent $e
     * @return void
     */
    public function onBootstrap($e)
    {
        $app      = $e->getTarget();
        $events   = $app->getEventManager();
        $services = $app->getServiceManager();
        $services->get('Laminas\ApiTools\Versioning\AcceptListener')->attach($events);
        $services->get('Laminas\ApiTools\Versioning\ContentTypeListener')->attach($events);
        $services->get('Laminas\ApiTools\Versioning\VersionListener')->attach($events);
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
