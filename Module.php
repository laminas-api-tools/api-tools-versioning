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
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array('Laminas\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__ . '/src/',
        )));
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array('factories' => array(
            'Laminas\ApiTools\Versioning\AcceptListener' => function ($services) {
                $config = array();
                if ($services->has('Config')) {
                    $allConfig = $services->get('Config');
                    if (isset($allConfig['api-tools-versioning'])
                        && isset($allConfig['api-tools-versioning']['content-type'])
                        && is_array($allConfig['api-tools-versioning']['content-type'])
                    ) {
                        $config = $allConfig['api-tools-versioning']['content-type'];
                    }
                }

                $listener = new AcceptListener();
                foreach ($config as $regexp) {
                    $listener->addRegexp($regexp);
                }
                return $listener;
            },
            'Laminas\ApiTools\Versioning\ContentTypeListener' => function ($services) {
                $config = array();
                if ($services->has('Config')) {
                    $allConfig = $services->get('Config');
                    if (isset($allConfig['api-tools-versioning'])
                        && isset($allConfig['api-tools-versioning']['content-type'])
                        && is_array($allConfig['api-tools-versioning']['content-type'])
                    ) {
                        $config = $allConfig['api-tools-versioning']['content-type'];
                    }
                }

                $listener = new ContentTypeListener();
                foreach ($config as $regexp) {
                    $listener->addRegexp($regexp);
                }
                return $listener;
            },
        ));
    }

    public function init($moduleManager)
    {
        $events = $moduleManager->getEventManager();
        $prototypeRouteListener = new PrototypeRouteListener();
        $events->attach($prototypeRouteListener);
    }

    public function onBootstrap($e)
    {
        $app      = $e->getTarget();
        $events   = $app->getEventManager();
        $services = $app->getServiceManager();
        $events->attach($services->get('Laminas\ApiTools\Versioning\AcceptListener'));
        $events->attach($services->get('Laminas\ApiTools\Versioning\ContentTypeListener'));
        $events->attach($services->get('Laminas\ApiTools\Versioning\VersionListener'));
    }
}
