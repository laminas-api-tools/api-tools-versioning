<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Versioning;

use Laminas\ApiTools\Versioning\AcceptListener;
use Laminas\ApiTools\Versioning\ContentTypeListener;
use Laminas\ApiTools\Versioning\Module;
use Laminas\ApiTools\Versioning\PrototypeRouteListener;
use Laminas\ApiTools\Versioning\VersionListener;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

use function sprintf;

class ModuleTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    public function setUp(): void
    {
        $this->app      = new TestAsset\Application();
        $this->services = new ServiceManager();
        $this->app->setServiceManager($this->services);
        $this->events = new EventManager();
        $this->app->setEventManager($this->events);

        $this->module = new Module();
    }

    public function testOnBootstrapMethodRegistersListenersWithEventManager()
    {
        $config = include __DIR__ . '/../config/module.config.php';
        (new Config($config['service_manager']))->configureServiceManager($this->services);

        $event = new MvcEvent();
        $event->setTarget($this->app);

        $this->module->onBootstrap($event);

        $listeners = [
            ContentTypeListener::class => -40,
            AcceptListener::class      => -40,
            VersionListener::class     => -41,
        ];

        foreach ($listeners as $class => $priority) {
            $listener = $this->services->get($class);
            $this->assertListenerAtPriority(
                [$listener, 'onRoute'],
                $priority,
                MvcEvent::EVENT_ROUTE,
                $this->events,
                sprintf('Listener %s at priority %s was not registered', $class, $priority)
            );
        }
    }

    public function testInitMethodRegistersPrototypeListenerWithModuleEventManager()
    {
        $moduleManager = new ModuleManager([]);
        $this->module->init($moduleManager);

        $listener = $this->module->getPrototypeRouteListener();
        $this->assertInstanceOf(PrototypeRouteListener::class, $listener);

        $events = $moduleManager->getEventManager();
        $this->assertListenerAtPriority(
            [$listener, 'onMergeConfig'],
            1,
            ModuleEvent::EVENT_MERGE_CONFIG,
            $events
        );
    }
}
