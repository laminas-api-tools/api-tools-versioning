<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Versioning;

use Laminas\ApiTools\Versioning\ContentTypeListener;
use Laminas\ApiTools\Versioning\Module;
use Laminas\EventManager\EventManager;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class ModuleTest extends TestCase
{
    public function setUp()
    {
        $this->app = new TestAsset\Application();
        $this->services = new ServiceManager();
        $this->app->setServiceManager($this->services);
        $this->events = new EventManager();
        $this->app->setEventManager($this->events);

        $this->module = new Module();
    }

    public function testModuleDefinesServiceForContentTypeListener()
    {
        $config = $this->module->getServiceConfig();
        $this->assertArrayHasKey('factories', $config);
        $this->assertArrayHasKey('Laminas\ApiTools\Versioning\ContentTypeListener', $config['factories']);
        $this->assertInstanceOf('Closure', $config['factories']['Laminas\ApiTools\Versioning\ContentTypeListener']);
        return $config['factories']['Laminas\ApiTools\Versioning\ContentTypeListener'];
    }

    /**
     * @depends testModuleDefinesServiceForContentTypeListener
     */
    public function testModuleDefinesServiceForAcceptListener($factory)
    {
        $config = $this->module->getServiceConfig();
        $this->assertArrayHasKey('factories', $config);
        $this->assertArrayHasKey('Laminas\ApiTools\Versioning\AcceptListener', $config['factories']);
        $this->assertInstanceOf('Closure', $config['factories']['Laminas\ApiTools\Versioning\AcceptListener']);
    }

    /**
     * @depends testModuleDefinesServiceForContentTypeListener
     */
    public function testServiceFactoryDefinedInModuleReturnsListener($factory)
    {
        $listener = $factory($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\Versioning\ContentTypeListener', $listener);
    }

    /**
     * @depends testModuleDefinesServiceForContentTypeListener
     */
    public function testServiceFactoryDefinedInModuleUsesConfigServiceWhenDefiningListener($factory)
    {
        $config = array(
            'api-tools-versioning' => array(
                'content-type' => array(
                    '#^application/vendor\.(?P<vendor>mwop)\.(?P<resource>user|status)$#',
                ),
            ),
        );
        $this->services->setService('config', $config);

        $listener = $factory($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\Versioning\ContentTypeListener', $listener);
        $this->assertAttributeContains($config['api-tools-versioning']['content-type'][0], 'regexes', $listener);
    }

    /**
     * @depends testModuleDefinesServiceForContentTypeListener
     */
    public function testOnBootstrapMethodRegistersListenersWithEventManager($factory)
    {
        $serviceConfig = $this->module->getServiceConfig();
        $this->services->setFactory(
            'Laminas\ApiTools\Versioning\ContentTypeListener',
            $serviceConfig['factories']['Laminas\ApiTools\Versioning\ContentTypeListener']
        );
        $this->services->setFactory(
            'Laminas\ApiTools\Versioning\AcceptListener',
            $serviceConfig['factories']['Laminas\ApiTools\Versioning\AcceptListener']
        );
        $this->services->setInvokableClass('Laminas\ApiTools\Versioning\VersionListener', 'Laminas\ApiTools\Versioning\VersionListener');

        $event = new MvcEvent();
        $event->setTarget($this->app);

        $this->module->onBootstrap($event);

        $listeners = $this->events->getListeners(MvcEvent::EVENT_ROUTE);
        $this->assertEquals(3, count($listeners));
        $this->assertTrue($listeners->hasPriority(-40));

        $test = array();
        foreach ($listeners as $listener) {
            $callback = $listener->getCallback();
            $test[]   = array_shift($callback);
        }

        $expected = array(
            'Laminas\ApiTools\Versioning\ContentTypeListener',
            'Laminas\ApiTools\Versioning\AcceptListener',
            'Laminas\ApiTools\Versioning\VersionListener',
        );
        foreach ($expected as $class) {
            $listener = $this->services->get($class);
            $this->assertContains($listener, $test);
        }
    }

    public function testInitMethodRegistersPrototypeListenerWithModuleEventManager()
    {
        $moduleManager = new ModuleManager(array());
        $this->module->init($moduleManager);

        $events    = $moduleManager->getEventManager();
        $listeners = $events->getListeners(ModuleEvent::EVENT_MERGE_CONFIG);
        $this->assertEquals(1, count($listeners));
        $this->assertTrue($listeners->hasPriority(1));
        $callback = $listeners->getIterator()->current()->getCallback();
        $test     = array_shift($callback);
        $this->assertInstanceOf('Laminas\ApiTools\Versioning\PrototypeRouteListener', $test);
    }
}
