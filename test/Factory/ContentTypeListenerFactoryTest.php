<?php

namespace LaminasTest\ApiTools\Versioning\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Versioning\ContentTypeListener;
use Laminas\ApiTools\Versioning\Factory\ContentTypeListenerFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ContentTypeListenerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);

        $r = new ReflectionClass(ContentTypeListener::class);
        $props = $r->getDefaultProperties();
        $this->defaultRegexes = $props['regexes'];
    }

    public function testCreatesEmptyContentTypeListenerIfNoConfigServicePresent()
    {
        $this->container->has('config')->willReturn(false);
        $factory = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertAttributeSame($this->defaultRegexes, 'regexes', $listener);
    }

    public function testCreatesEmptyContentTypeListenerIfNoVersioningConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['foo' => 'bar']);
        $factory = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertAttributeSame($this->defaultRegexes, 'regexes', $listener);
    }

    public function testCreatesEmptyContentTypeListenerIfNoVersioningContentTypeConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['api-tools-versioning' => ['foo' => 'bar']]);
        $factory = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertAttributeSame($this->defaultRegexes, 'regexes', $listener);
    }

    public function testConfiguresContentTypeListeneWithRegexesFromConfiguration()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['api-tools-versioning' => [
            'content-type' => [
                '#foo=bar#',
            ],
        ]]);
        $factory = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertAttributeContains('#foo=bar#', 'regexes', $listener);

        foreach ($this->defaultRegexes as $regex) {
            $this->assertAttributeContains($regex, 'regexes', $listener);
        }
    }
}
