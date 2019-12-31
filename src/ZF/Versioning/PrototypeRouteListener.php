<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Versioning;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\ModuleManager\Listener\ConfigListener;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\Stdlib\ArrayUtils;

class PrototypeRouteListener extends AbstractListenerAggregate
{
    /**
     * Match to prepend to versioned routes
     * 
     * @var string
     */
    protected $versionRoutePrefix = '[/v:version]';

    /**
     * Constraints to introduce in versioned routes
     * 
     * @var array
     */
    protected $versionRouteOptions = array(
        'defaults'    => array(
            'version' => 1,
        ),
        'constraints' => array(
            'version' => '\d+',
        ),
    );

    /**
     * Attach listener to ModuleEvent::EVENT_MERGE_CONFIG
     *
     * @param  EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, array($this, 'onMergeConfig'));
    }

    /**
     * Listen to ModuleEvent::EVENT_MERGE_CONFIG
     *
     * Looks for api-tools-versioning.url and router configuration; if both present,
     * injects the route prototype and adds a chain route to each route listed
     * in the api-tools-versioning.url array.
     *
     * @param  ModuleEvent $e
     */
    public function onMergeConfig(ModuleEvent $e)
    {
        $configListener = $e->getConfigListener();
        if (!$configListener instanceof ConfigListener) {
            return;
        }

        $config = $configListener->getMergedConfig(false);

        // Check for config keys
        if (!isset($config['api-tools-versioning'])
            || !isset($config['router'])
        ) {
            return;
        }

        // Do we need to inject a prototype?
        if (!isset($config['api-tools-versioning']['uri'])
            || !is_array($config['api-tools-versioning']['uri'])
            || empty($config['api-tools-versioning']['uri'])
        ) {
            return;
        }

        // Override default version of 1 with user-specified config value, if available.
        if (isset($config['api-tools-versioning']['default_version'])) {
            $this->versionRouteOptions['defaults']['version'] = $config['api-tools-versioning']['default_version'];
        }

        // Pre-process route list to strip out duplicates (often a result of
        // specifying nested routes)
        $routes   = $config['api-tools-versioning']['uri'];
        $filtered = array();
        foreach ($routes as $index => $route) {
            if (strstr($route, '/')) {
                $temp  = explode('/', $route, 2);
                $route = array_shift($temp);
            }
            if (in_array($route, $filtered)) {
                continue;
            }
            $filtered[] = $route;
        }
        $routes = $filtered;

        // Inject chained routes
        foreach ($routes as $routeName) {
            if (!isset($config['router']['routes'][$routeName])) {
                continue;
            }

            $config['router']['routes'][$routeName]['options']['route'] = $this->versionRoutePrefix
                . $config['router']['routes'][$routeName]['options']['route'];

            $config['router']['routes'][$routeName]['options'] = ArrayUtils::merge(
                $config['router']['routes'][$routeName]['options'],
                $this->versionRouteOptions
            );
        }

        // Reset merged config
        $configListener->setMergedConfig($config);
    }
}
