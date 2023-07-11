<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Versioning\Factory;

use Laminas\ApiTools\Versioning\ContentTypeListener;
use Psr\Container\ContainerInterface;

class ContentTypeListenerFactory
{
    /**
     * @return ContentTypeListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['api-tools-versioning']['content-type'] ?? [];

        $listener = new ContentTypeListener();
        foreach ($config as $regexp) {
            $listener->addRegexp($regexp);
        }
        return $listener;
    }
}
