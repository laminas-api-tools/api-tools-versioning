<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-versioning for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-versioning/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-versioning/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Versioning\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Versioning\ContentTypeListener;

class ContentTypeListenerFactory
{
    /**
     * @param ContainerInterface $container
     * @return ContentTypeListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = isset($config['api-tools-versioning']['content-type'])
            ? $config['api-tools-versioning']['content-type']
            : [];

        $listener = new ContentTypeListener();
        foreach ($config as $regexp) {
            $listener->addRegexp($regexp);
        }
        return $listener;
    }
}
