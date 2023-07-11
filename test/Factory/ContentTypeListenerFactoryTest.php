<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Versioning\Factory;

use Laminas\ApiTools\Versioning\ContentTypeListener;
use Laminas\ApiTools\Versioning\Factory\ContentTypeListenerFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class ContentTypeListenerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);

        $r                    = new ReflectionClass(ContentTypeListener::class);
        $props                = $r->getDefaultProperties();
        $this->defaultRegexes = $props['regexes'];
    }

    public function testCreatesEmptyContentTypeListenerIfNoConfigServicePresent(): void
    {
        $this->container->has('config')->willReturn(false);
        $factory  = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
    }

    public function testCreatesEmptyContentTypeListenerIfNoVersioningConfigPresent(): void
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['foo' => 'bar']);
        $factory  = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
    }

    public function testCreatesEmptyContentTypeListenerIfNoVersioningContentTypeConfigPresent(): void
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['api-tools-versioning' => ['foo' => 'bar']]);
        $factory  = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
    }

    public function testConfiguresContentTypeListeneWithRegexesFromConfiguration(): void
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'api-tools-versioning' => [
                'content-type' => [
                    '#foo=bar#',
                ],
            ],
        ]);
        $factory  = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $actualRegexes = self::getActualRegexes($listener);
        $this->assertContains('#foo=bar#', $actualRegexes);

        foreach ($this->defaultRegexes as $regex) {
            $this->assertContains($regex, $actualRegexes);
        }
    }

    private static function getActualRegexes(ContentTypeListener $listener): array
    {
        $reflectionClass    = new ReflectionClass(ContentTypeListener::class);
        $reflectionProperty = $reflectionClass->getProperty('regexes');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($listener);
    }
}
