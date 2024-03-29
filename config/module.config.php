<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Versioning;

use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'api-tools-versioning' => [
        'content-type' => [
            // @codingStandardsIgnoreStart
            // Array of regular expressions to apply against the content-type
            // header. All capturing expressions should be named:
            // (?P<name_to_capture>expression)
            // Default: '#^application/vnd\.(?P<laminas_ver_vendor>[^.]+)\.v(?P<laminas_ver_version>\d+)\.(?P<laminas_ver_resource>[a-zA-Z0-9_-]+)$#'
            //
            // Example:
            // '#^application/vendor\.(?P<vendor>mwop)\.v(?P<version>\d+)\.(?P<resource>status|user)$#',
            // @codingStandardsIgnoreEnd
        ],
        // Default version number to use if none is provided by the API consumer. Default: 1
        'default_version' => 1,
        'uri'             => [
            // Array of routes that should prepend the "api-tools-versioning" route
            // (i.e., "/v:version"). Any route in this array will be chained to
            // that route, but can still be referenced by their route name.
            //
            // If the route is a child route, the chain will happen against the
            // top-most ancestor.
            //
            // Example:
            //     "api", "status", "user"
            //
            // would chain the above named routes, and version them.
        ],
    ],
    'service_manager'      => [
        'factories' => [
            AcceptListener::class      => Factory\AcceptListenerFactory::class,
            ContentTypeListener::class => Factory\ContentTypeListenerFactory::class,
            VersionListener::class     => InvokableFactory::class,
        ],
    ],
];
