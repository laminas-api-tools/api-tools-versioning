<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Versioning;

use Laminas\ApiTools\Versioning\PrototypeRouteListener;
use Laminas\ModuleManager\Listener\ConfigListener;
use Laminas\ModuleManager\ModuleEvent;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function strpos;
use function var_export;

class PrototypeRouteListenerTest extends TestCase
{
    public function setUp(): void
    {
        $this->config         = [
            'router' => [
                'routes' => [
                    'status' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/status[/:id]',
                            'defaults' => [
                                'controller' => 'StatusController',
                            ],
                        ],
                    ],
                    'user'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/user[/:id]',
                            'defaults' => [
                                'controller' => 'UserController',
                            ],
                        ],
                    ],
                    'group'  => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/group[/v:version][/:id]',
                            'defaults' => [
                                'controller' => 'GroupController',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->configListener = new ConfigListener();
        $this->configListener->setMergedConfig($this->config);
        $this->event = new ModuleEvent();
        $this->event->setConfigListener($this->configListener);
    }

    /** @return array */
    public function routesWithoutPrototype()
    {
        return [
            'none'   => [[]],
            'status' => [['status']],
            'user'   => [['user']],
            'both'   => [['status', 'user']],
        ];
    }

    /**
     * @dataProvider routesWithoutPrototype
     * @param array $routes
     */
    public function testEmptyConfigurationDoesNotInjectPrototypes(array $routes)
    {
        $listener = new PrototypeRouteListener();
        $listener->onMergeConfig($this->event);

        $config = $this->configListener->getMergedConfig(false);
        $this->assertArrayHasKey('router', $config, var_export($config, 1));
        $routerConfig = $config['router'];
        $this->assertArrayNotHasKey('prototypes', $routerConfig);

        $routesConfig = $routerConfig['routes'];
        foreach ($routes as $routeName) {
            $this->assertArrayHasKey($routeName, $routesConfig);
            $routeConfig = $routesConfig[$routeName];
            $this->assertArrayNotHasKey('chain_routes', $routeConfig);
        }
    }

    /** @return array */
    public function routesForWhichToVerifyPrototype()
    {
        return [
            'status' => [['status'], 1],
            'user'   => [['user'], 2],
            'both'   => [['status', 'user'], null],
            'group'  => [['group'], null, 6],
        ];
    }

    /**
     * @dataProvider routesForWhichToVerifyPrototype
     * @param int|null $apiVersion
     * @param int $position
     */
    public function testPrototypeAddedToRoutesProvidedToListener(array $routes, $apiVersion = null, $position = 0)
    {
        $this->config['api-tools-versioning'] = [
            'uri' => $routes,
        ];

        if (! empty($apiVersion)) {
            $this->config['api-tools-versioning']['default_version'] = $apiVersion;
        } else {
            $apiVersion = 1;
        }

        $this->configListener->setMergedConfig($this->config);
        $listener = new PrototypeRouteListener();
        $listener->onMergeConfig($this->event);

        $config = $this->configListener->getMergedConfig(false);
        $this->assertArrayHasKey('router', $config, var_export($config, 1));
        $routerConfig = $config['router'];

        $routesConfig = $routerConfig['routes'];
        foreach ($routes as $routeName) {
            $this->assertArrayHasKey($routeName, $routesConfig);
            $routeConfig = $routesConfig[$routeName];
            $this->assertArrayHasKey('options', $routeConfig);
            $options = $routeConfig['options'];

            $this->assertArrayHasKey('route', $options);
            $this->assertSame($position, strpos($options['route'], '[/v:version]'));

            $this->assertArrayHasKey('constraints', $options);
            $this->assertArrayHasKey('version', $options['constraints']);
            $this->assertEquals('\d+', $options['constraints']['version']);

            $this->assertArrayHasKey('defaults', $options);
            $this->assertArrayHasKey('version', $options['defaults']);
            $this->assertEquals($apiVersion, $options['defaults']['version']);
        }
    }

    /** @return array */
    public function defaultVersionValues()
    {
        return [
            'v1'    => [1],
            'v2'    => [2],
            'empty' => [null],
        ];
    }

    /**
     * @dataProvider defaultVersionValues
     * @param int|null $apiVersion
     */
    public function testPrototypeAddedToRoutesWithDefaultVersion($apiVersion = null)
    {
        $routes                               = array_keys($this->config['router']['routes']);
        $this->config['api-tools-versioning'] = [
            'default_version' => $apiVersion,
            'uri'             => $routes,
        ];

        $this->configListener->setMergedConfig($this->config);
        $listener = new PrototypeRouteListener();
        $listener->onMergeConfig($this->event);

        $config = $this->configListener->getMergedConfig(false);
        $this->assertArrayHasKey('router', $config, var_export($config, 1));
        $routerConfig = $config['router'];

        $routesConfig = $routerConfig['routes'];

        foreach ($routes as $routeName) {
            $this->assertArrayHasKey($routeName, $routesConfig);
            $routeConfig = $routesConfig[$routeName];
            $this->assertArrayHasKey('options', $routeConfig);
            $options = $routeConfig['options'];

            $this->assertArrayHasKey('constraints', $options);
            $this->assertArrayHasKey('version', $options['constraints']);
            $this->assertEquals('\d+', $options['constraints']['version']);

            $this->assertArrayHasKey('defaults', $options);
            $this->assertArrayHasKey('version', $options['defaults']);

            $apiVersion = $apiVersion ?? 1;
            $this->assertEquals($apiVersion, $options['defaults']['version']);
        }
    }

    /** @return array */
    public function specificDefaultVersionForWhichToVerifyPrototype()
    {
        return [
            'status'           => [['status' => 2]],
            'user'             => [['user' => 4]],
            'all-except-group' => [['status' => 2, 'user' => 3]],
        ];
    }

    /**
     * @dataProvider specificDefaultVersionForWhichToVerifyPrototype
     */
    public function testPrototypeAddedToRoutesWithSpecificDefaultVersion(array $defaultVersions)
    {
        $routes                               = array_keys($this->config['router']['routes']);
        $this->config['api-tools-versioning'] = [
            'default_version' => $defaultVersions,
            'uri'             => $routes,
        ];

        $this->configListener->setMergedConfig($this->config);
        $listener = new PrototypeRouteListener();
        $listener->onMergeConfig($this->event);

        $config = $this->configListener->getMergedConfig(false);
        $this->assertArrayHasKey('router', $config, var_export($config, 1));
        $routerConfig = $config['router'];

        $routesConfig = $routerConfig['routes'];

        foreach ($routes as $routeName) {
            $this->assertArrayHasKey($routeName, $routesConfig);
            $routeConfig = $routesConfig[$routeName];
            $this->assertArrayHasKey('options', $routeConfig);
            $options = $routeConfig['options'];

            $this->assertArrayHasKey('constraints', $options);
            $this->assertArrayHasKey('version', $options['constraints']);
            $this->assertEquals('\d+', $options['constraints']['version']);

            $this->assertArrayHasKey('defaults', $options);
            $this->assertArrayHasKey('version', $options['defaults']);
            $apiVersion = $defaultVersions[$routeName] ?? 1;
            $this->assertEquals($apiVersion, $options['defaults']['version']);
        }
    }
}
