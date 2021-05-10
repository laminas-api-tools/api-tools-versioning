<?php

namespace Laminas\ApiTools\Versioning;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\RouteMatch as V2RouteMatch;
use Laminas\Router\RouteMatch;

class ContentTypeListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * Header to examine.
     * @var string
     */
    protected $headerName = 'content-type';

    // @codingStandardsIgnoreStart
    /**
     * @var array
     */
    protected $regexes = [
        '#^application/vnd\.(?P<laminas_ver_vendor>[^.]+)\.v(?P<laminas_ver_version>\d+)(?:\.(?P<laminas_ver_resource>[a-zA-Z0-9_-]+))?(?:\+[a-z]+)?$#',
    ];
    // @codingStandardsIgnoreEnd

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], -40);
    }

    /**
     * Add a regular expression to the stack
     *
     * @param  string $regex
     * @return self
     */
    public function addRegexp($regex)
    {
        if (! is_string($regex)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string regular expression as an argument; received %s',
                __METHOD__,
                (is_object($regex) ? get_class($regex) : gettype($regex))
            ));
        }
        $this->regexes[] = $regex;
        return $this;
    }

    /**
     * Match against the Content-Type header and inject into the route matches
     *
     * @param MvcEvent $e
     */
    public function onRoute(MvcEvent $e)
    {
        $routeMatches = $e->getRouteMatch();
        if (! ($routeMatches instanceof RouteMatch || $routeMatches instanceof V2RouteMatch)) {
            return;
        }

        $request = $e->getRequest();
        if (! $request instanceof Request) {
            return;
        }

        $headers = $request->getHeaders();
        if (! $headers->has($this->headerName)) {
            return;
        }

        $header = $headers->get($this->headerName);

        $matches = $this->parseHeaderForMatches($header->getFieldValue());
        if (is_array($matches)) {
            $this->injectRouteMatches($routeMatches, $matches);
        }
    }

    /**
     * Parse the header for matches against registered regexes
     *
     * @param  string $value
     * @return false|array
     */
    protected function parseHeaderForMatches($value)
    {
        $parts = explode(';', $value);
        $contentType = array_shift($parts);
        $contentType = trim($contentType);

        foreach (array_reverse($this->regexes) as $regex) {
            if (! preg_match($regex, $contentType, $matches)) {
                continue;
            }

            return $matches;
        }

        return false;
    }

    /**
     * Inject regex matches into the route matches
     *
     * @param  RouteMatch|V2RouteMatch $routeMatches
     * @param  array $matches
     */
    protected function injectRouteMatches($routeMatches, array $matches)
    {
        foreach ($matches as $key => $value) {
            if (is_numeric($key) || is_int($key) || $value === '') {
                continue;
            }
            $routeMatches->setParam($key, $value);
        }
    }
}
