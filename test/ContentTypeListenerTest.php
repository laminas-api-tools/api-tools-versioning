<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Versioning;

use Laminas\ApiTools\Versioning\ContentTypeListener;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class ContentTypeListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;
    use RouteMatchFactoryTrait;

    public function setUp(): void
    {
        $this->event = new MvcEvent();
        $this->event->setRequest(new Request());
        $this->event->setRouteMatch($this->createRouteMatch([]));

        $this->listener = new ContentTypeListener();
    }

    public function testAttachesToRouteEventAtNegativePriority()
    {
        $events = new EventManager();
        $this->listener->attach($events);

        $this->assertListenerAtPriority(
            [$this->listener, 'onRoute'],
            -40,
            MvcEvent::EVENT_ROUTE,
            $events
        );
    }

    public function testDoesNothingIfNoRouteMatchPresentInEvent()
    {
        $event = new MvcEvent();
        $event->setRequest(new Request());
        $this->assertNull($this->listener->onRoute($event));
    }

    public function testDoesNothingIfNoRequestPresentInEvent()
    {
        $event = new MvcEvent();
        $event->setRouteMatch($this->createRouteMatch([]));
        $this->assertNull($this->listener->onRoute($event));
    }

    public function testInjectsNothingIfContentTypeHeaderIsMissing()
    {
        $this->assertNull($this->listener->onRoute($this->event));
    }

    /** @return array */
    public function validDefaultContentTypes()
    {
        return [
            [
                'application/vnd.mwop.v1.status',
                'mwop',
                1,
                'status',
            ],
            [
                'application/vnd.laminas.v2.user',
                'laminas',
                2,
                'user',
            ],
        ];
    }

    /**
     * @dataProvider validDefaultContentTypes
     * @param string $header
     * @param string $vendor
     * @param int $version
     * @param string $resource
     */
    public function testInjectsRouteMatchesWhenContentTypeMatchesDefaultRegexp($header, $vendor, $version, $resource)
    {
        $request = $this->event->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaderLine('Content-Type', $header);

        $this->listener->onRoute($this->event);
        $routeMatch = $this->event->getRouteMatch();
        $this->assertEquals($vendor, $routeMatch->getParam('laminas_ver_vendor', false));
        $this->assertEquals($version, $routeMatch->getParam('laminas_ver_version', false));
        $this->assertEquals($resource, $routeMatch->getParam('laminas_ver_resource', false));
    }

    /** @return array */
    public function invalidDefaultContentTypes()
    {
        return [
            'bad-prefix'                   => ['application/vendor.mwop.v1.status'],
            'bad-version'                  => ['application/vnd.laminas.2.user'],
            'missing-version'              => ['application/vnd.laminas.user'],
            'missing-version-and-resource' => ['application/vnd.laminas'],
        ];
    }

    /**
     * @dataProvider invalidDefaultContentTypes
     * @param string $header
     */
    public function testInjectsNothingIntoRouteMatchesWhenContentTypeDoesNotMatchDefaultRegexp($header)
    {
        $request = $this->event->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaderLine('Content-Type', $header);

        $this->listener->onRoute($this->event);
        $routeMatch = $this->event->getRouteMatch();
        $this->assertFalse($routeMatch->getParam('laminas_ver_vendor', false));
        $this->assertFalse($routeMatch->getParam('laminas_ver_version', false));
        $this->assertFalse($routeMatch->getParam('laminas_ver_resource', false));
    }

    /** @return array */
    public function validCustomContentTypes()
    {
        return [
            [
                'application/vendor.mwop.1.status',
                'mwop',
                1,
                'status',
            ],
            [
                'application/vendor.mwop.2.user',
                'mwop',
                2,
                'user',
            ],
        ];
    }

    /**
     * @dataProvider validCustomContentTypes
     * @param string $header
     * @param string $vendor
     * @param int $version
     * @param string $resource
     */
    public function testWillInjectRouteMatchesWhenContentTypeMatchesCustomRegexp($header, $vendor, $version, $resource)
    {
        $this->listener->addRegexp(
            '#application/vendor\.(?<vendor>mwop)\.(?<version>\d+)\.(?<resource>(?:user|status))#'
        );

        $request = $this->event->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaderLine('Content-Type', $header);

        $this->listener->onRoute($this->event);
        $routeMatch = $this->event->getRouteMatch();
        $this->assertEquals('mwop', $routeMatch->getParam('vendor', false));
        $this->assertEquals($version, $routeMatch->getParam('version', false));
        $this->assertEquals($resource, $routeMatch->getParam('resource', false));
    }

    /** @return array */
    public function mixedContentTypes()
    {
        return [
            'default' => [
                'application/vnd.mwop.v1.status',
                [
                    'laminas_ver_vendor'   => 'mwop',
                    'laminas_ver_version'  => 1,
                    'laminas_ver_resource' => 'status',
                ],
            ],
            'custom'  => [
                'application/vnd.mwop.1.status',
                [
                    'vendor'   => 'mwop',
                    'version'  => 1,
                    'resource' => 'status',
                ],
            ],
        ];
    }

    /**
     * @dataProvider mixedContentTypes
     * @param string $header
     * @param array $matches
     */
    public function testWillInjectRouteMatchesForFirstRegexpToMatch($header, array $matches)
    {
        $this->listener->addRegexp('#application/vnd\.(?<vendor>mwop)\.(?<version>\d+)\.(?<resource>(?:user|status))#');

        $request = $this->event->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaderLine('Content-Type', $header);

        $this->listener->onRoute($this->event);
        $routeMatch = $this->event->getRouteMatch();
        foreach ($matches as $key => $expected) {
            $this->assertEquals($expected, $routeMatch->getParam($key, false));
        }
    }
}
