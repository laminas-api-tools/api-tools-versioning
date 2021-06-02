<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Versioning;

use Laminas\Mvc\Router\RouteMatch as V2RouteMatch;
use Laminas\Router\RouteMatch;

use function class_exists;

trait RouteMatchFactoryTrait
{
    /**
     * Create and return a version-specific RouteMatch instance.
     *
     * @param array $params
     * @return RouteMatch|V2RouteMatch
     */
    public function createRouteMatch(array $params = [])
    {
        $class = $this->getRouteMatchClass();
        return new $class($params);
    }

    /**
     * Get the version-specific RouteMatch class to use.
     *
     * @return string
     */
    public function getRouteMatchClass()
    {
        return class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
    }
}
