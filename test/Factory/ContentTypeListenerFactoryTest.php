<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Versioning\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Versioning\ContentTypeListener;
use Laminas\ApiTools\Versioning\Factory\ContentTypeListenerFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
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

    public function testCreatesEmptyContentTypeListenerIfNoConfigServicePresent()
    {
        $this->container->has('config')->willReturn(false);
        $factory  = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
    }

    public function testCreatesEmptyContentTypeListenerIfNoVersioningConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['foo' => 'bar']);
        $factory  = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
    }

    public function testCreatesEmptyContentTypeListenerIfNoVersioningContentTypeConfigPresent()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['api-tools-versioning' => ['foo' => 'bar']]);
        $factory  = new ContentTypeListenerFactory();
        $listener = $factory($this->container->reveal());
        $this->assertInstanceOf(ContentTypeListener::class, $listener);
        $this->assertSame($this->defaultRegexes, self::getActualRegexes($listener));
    }

    public function testConfiguresContentTypeListeneWithRegexesFromConfiguration()
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
