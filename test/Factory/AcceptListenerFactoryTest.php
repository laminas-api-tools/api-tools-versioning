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
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionClass;

class AcceptListenerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function setUp(): void
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
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
    }

    public function testCreatesEmptyAcceptListenerIfNoVersioningConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['foo' => 'bar']);
        $factory = new AcceptListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(AcceptListener::class, $listener);
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
    }

    public function testCreatesEmptyAcceptListenerIfNoVersioningContentTypeConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['api-tools-versioning' => ['foo' => 'bar']]);
        $factory = new AcceptListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(AcceptListener::class, $listener);
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
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
        $actualRegexes = self::getActualRegexes($listener);
        $this->assertContains('#foo=bar#', $actualRegexes);

        foreach ($this->defaultRegexes as $regex) {
            $this->assertContains($regex, $actualRegexes);
        }
    }

    private static function getActualRegexes(AcceptListener $listener): array
    {
        $reflectionClass = new ReflectionClass(AcceptListener::class);
        $reflectionProperty = $reflectionClass->getProperty('regexes');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($listener);
    }
}
