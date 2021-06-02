<?php

declare(strict_types=1);

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

    public function testAttachesToRouteEventAtNegativePriority(): void
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

    public function testDoesNothingIfNoRouteMatchPresentInEvent(): void
    {
        $event = new MvcEvent();
        $event->setRequest(new Request());
        $this->assertNull($this->listener->onRoute($event));
    }

    public function testDoesNothingIfNoRequestPresentInEvent(): void
    {
        $event = new MvcEvent();
        $event->setRouteMatch($this->createRouteMatch([]));
        $this->assertNull($this->listener->onRoute($event));
    }

    public function testInjectsNothingIfContentTypeHeaderIsMissing(): void
    {
        $this->assertNull($this->listener->onRoute($this->event));
    }

    /**
     * @psalm-return array<array-key, array{
     *     0: string,
     *     1: string,
     *     2: int,
     *     3: string
     * }>
     */
    public function validDefaultContentTypes(): array
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
     */
    public function testInjectsRouteMatchesWhenContentTypeMatchesDefaultRegexp(
        string $header,
        string $vendor,
        int $version,
        string $resource
    ): void {
        $request = $this->event->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaderLine('Content-Type', $header);

        $this->listener->onRoute($this->event);
        $routeMatch = $this->event->getRouteMatch();
        $this->assertEquals($vendor, $routeMatch->getParam('laminas_ver_vendor', false));
        $this->assertEquals($version, $routeMatch->getParam('laminas_ver_version', false));
        $this->assertEquals($resource, $routeMatch->getParam('laminas_ver_resource', false));
    }

    /** @psalm-return array<string, array{0: string}> */
    public function invalidDefaultContentTypes(): array
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
     */
    public function testInjectsNothingIntoRouteMatchesWhenContentTypeDoesNotMatchDefaultRegexp(string $header): void
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

    /**
     * @psalm-return array<array-key, array{
     *     0: string,
     *     1: string,
     *     2: int,
     *     3: string
     * }>
     */
    public function validCustomContentTypes(): array
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
     */
    public function testWillInjectRouteMatchesWhenContentTypeMatchesCustomRegexp(
        string $header,
        string $vendor,
        int $version,
        string $resource
    ): void {
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

    /**
     * @psalm-return array<string, array{
     *     0: string,
     *     1: array<string, string|int>
     * }>
     */
    public function mixedContentTypes(): array
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
     */
    public function testWillInjectRouteMatchesForFirstRegexpToMatch(string $header, array $matches): void
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
