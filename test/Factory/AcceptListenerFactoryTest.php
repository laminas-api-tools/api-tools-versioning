<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Versioning\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Versioning\AcceptListener;
use Laminas\ApiTools\Versioning\Factory\AcceptListenerFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AcceptListenerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);

        $r = new ReflectionClass(AcceptListener::class);
        $props = $r->getDefaultProperties();
        $this->defaultRegexes = $props['regexes'];
    }

    public function testCreatesEmptyAcceptListenerIfNoConfigServicePresent()
    {
        $this->container->has('config')->willReturn(false);
        $factory = new AcceptListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(AcceptListener::class, $listener);
        $this->assertAttributeSame($this->defaultRegexes, 'regexes', $listener);
    }

    public function testCreatesEmptyAcceptListenerIfNoVersioningConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['foo' => 'bar']);
        $factory = new AcceptListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(AcceptListener::class, $listener);
        $this->assertAttributeSame($this->defaultRegexes, 'regexes', $listener);
    }

    public function testCreatesEmptyAcceptListenerIfNoVersioningContentTypeConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['api-tools-versioning' => ['foo' => 'bar']]);
        $factory = new AcceptListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(AcceptListener::class, $listener);
        $this->assertAttributeSame($this->defaultRegexes, 'regexes', $listener);
    }

    public function testConfiguresAcceptListeneWithRegexesFromConfiguration()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['api-tools-versioning' => [
            'content-type' => [
                '#foo=bar#',
            ],
        ]]);
        $factory = new AcceptListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(AcceptListener::class, $listener);
        $this->assertAttributeContains('#foo=bar#', 'regexes', $listener);

        foreach ($this->defaultRegexes as $regex) {
            $this->assertAttributeContains($regex, 'regexes', $listener);
        }
    }
}
