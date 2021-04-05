<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Versioning;

use Laminas\ApiTools\Versioning\VersionListener;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class VersionListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;
    use ProphecyTrait;
    use RouteMatchFactoryTrait;

    public function setUp(): void
    {
        $this->event = new MvcEvent();
        $this->event->setRouteMatch($this->createRouteMatch([]));

        $this->listener = new VersionListener();
    }

    public function testAttachesToRouteEventAtNegativePriority()
    {
        $events = new EventManager();
        $this->listener->attach($events);

        $this->assertListenerAtPriority(
            [$this->listener, 'onRoute'],
            -41,
            MvcEvent::EVENT_ROUTE,
            $events
        );
    }

    public function testDoesNothingIfNoRouteMatchPresentInEvent()
    {
        $event = new MvcEvent();
        $this->assertNull($this->listener->onRoute($event));
    }

    public function testDoesNothingIfNoVersionAndNoLaminasVerVersionParameterInRouteMatch()
    {
        $this->assertNull($this->listener->onRoute($this->event));
    }

    public function testDoesNothingIfNoControllerParameterInRouteMatch()
    {
        $matches = $this->event->getRouteMatch();
        $matches->setParam('version', 2);
        $this->assertNull($this->listener->onRoute($this->event));
    }

    public function testDoesNothingIfControllerHasNoVersionNamespace()
    {
        $matches = $this->event->getRouteMatch();
        $matches->setParam('version', 2);
        $matches->setParam('controller', 'Foo\Bar\Controller');
        $this->assertNull($this->listener->onRoute($this->event));
    }

    public function testDoesNothingIfVersionAndControllerVersionNamespaceAreSame()
    {
        $matches = $this->event->getRouteMatch();
        $matches->setParam('version', 2);
        $matches->setParam('controller', 'Foo\V2\Rest\Bar\Controller');
        $this->assertNull($this->listener->onRoute($this->event));
    }

    public function testAltersControllerVersionNamespaceToReflectVersion()
    {
        $matches = $this->event->getRouteMatch();
        $matches->setParam('version', 2);
        $matches->setParam('controller', 'Foo\V1\Rest\Bar\Controller');
        $result = $this->listener->onRoute($this->event);
        $this->assertInstanceOf($this->getRouteMatchClass(), $result);
        $this->assertEquals('Foo\V2\Rest\Bar\Controller', $result->getParam('controller'));
    }

    /**
     * @group 12
     */
    public function testAltersControllerVersionNamespaceToReflectVersionForOptionsRequests()
    {
        $request = $this->prophesize(Request::class);
        $request->isOptions()->shouldNotBeCalled();

        $this->event->setRequest($request->reveal());

        $matches = $this->event->getRouteMatch();
        $matches->setParam('version', 2);
        $matches->setParam('controller', 'Foo\V1\Rest\Bar\Controller');
        $result = $this->listener->onRoute($this->event);
        $this->assertInstanceOf($this->getRouteMatchClass(), $result);
        $this->assertEquals('Foo\V2\Rest\Bar\Controller', $result->getParam('controller'));
    }
}
