<?php

namespace Laminas\ApiTools\Versioning\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Versioning\AcceptListener;

class AcceptListenerFactory
{
    /**
     * @param ContainerInterface $container
     * @return AcceptListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = isset($config['api-tools-versioning']['content-type'])
            ? $config['api-tools-versioning']['content-type']
            : [];

        $listener = new AcceptListener();
        foreach ($config as $regexp) {
            $listener->addRegexp($regexp);
        }
        return $listener;
    }
}
