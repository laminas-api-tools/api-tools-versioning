Laminas Versioning
=============

[![Build Status](https://github.com/laminas-api-tools/api-tools-versioning/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/laminas-api-tools/api-tools-versioning/actions/workflows/continuous-integration.yml)

> ## 🇷🇺 Русским гражданам
>
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
>
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
>
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
>
> ## 🇺🇸 To Citizens of Russia
>
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
>
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
>
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

Introduction
------------

api-tools-versioning is a Laminas module for automating service versioning through both URIs and `Accept` or
`Content-Type` header media types.  Information extracted from either the URI or header media type
that relates to versioning will be made available in the route match object.  In situations where a
controller service name is utilizing a sub-namespace matching the regexp `V(\d)`, the matched
controller service names will be updated with the currently matched version string.

Requirements
------------

Please see the [composer.json](composer.json) file.

Installation
------------

Run the following `composer` command:

```console
$ composer require laminas-api-tools/api-tools-versioning
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "laminas-api-tools/api-tools-versioning": "^1.2"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:


```php
return [
    /* ... */
    'modules' => [
        /* ... */
        'Laminas\ApiTools\Versioning',
    ],
    /* ... */
];
```

> ### laminas-component-installer
>
> If you use [laminas-component-installer](https://github.com/laminas/laminas-component-installer),
> that plugin will install api-tools-versioning as a module for you.


Configuration
-------------

### User Configuration

The top-level configuration key for user configuration of this module is `api-tools-versioning`.

#### Key: `content-type`

The `content-type` key is used for specifying an array of regular expressions that will be
used in parsing both `Content-Type` and `Accept` headers for media type based versioning
information.  A default regular expression is provided in the implementation which should
also serve as an example of what kind of regex to create for more specific parsing:

```php
'#^application/vnd\.(?P<laminas_ver_vendor>[^.]+)\.v(?P<laminas_ver_version>\d+)\.(?P<laminas_ver_resource>[a-zA-Z0-9_-]+)$#'
```

This rule will match the following pseudo-code route:

```
application/vnd.{api name}.v{version}(.{resource})?+json
```

All captured parts should utilize named parameters.  A more specific example, with the top-level key
would look like:

```php
'api-tools-versioning' => [
    'content-type' => [
        '#^application/vendor\.(?P<vendor>mwop)\.v(?P<version>\d+)\.(?P<resource>status|user)$#',
    ],
],
```

#### Key: `default_version`

The `default_version` key provides the default version number to use in case a version is not
provided by the client.  `1` is the default for `default_version`.

The setting accepts one of the two following possible values:

- A PHP `integer` indicating the default version number for *all* routes.
- An associative array, where the keys are route names, and the values the default version to use with the associated route.

Full Example:

```php
// Set v2 as default version for all routes
'api-tools-versioning' => [
    'default_version' => 2,
],
```

or

```php
// Set default version to v2 and v3 for the users and status routes respectively
'api-tools-versioning' => [
    'default_version' => [
        'myapi.rest.users' => 2,
        'myapi.rpc.status' => 3,
    ],
],
```

#### Key: `uri`

The `uri` key is responsible for identifying which routes need to be prepended with route matching
information for URL based versioning.  This key is an array of route names that is used in the Laminas
`router.routes` configuration.  If a particular route is a child route, the chain will happen at the
top-most ancestor.

The route matching segment consists of a rule of `[/v:version]` while specifying a constraint
of digits only for the version parameter.

Example:

```php
'api-tools-versioning' => [
    'uri' => [
        'api',
        'status',
        'user',
    ],
],
```

### System Configuration

The following configuration is provided in `config/module.config.php` to enable the module to
function:

```php
'service_manager' => [
    'factories' => [
        \Laminas\ApiTools\Versioning\AcceptListener::class => \Laminas\ApiTools\Versioning\Factory\AcceptListenerFactory::class,
        \Laminas\ApiTools\Versioning\ContentTypeListener::class => \Laminas\ApiTools\Versioning\Factory\ContentTypeListenerFactory::class,
        \Laminas\ApiTools\Versioning\VersionListener::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
    ],
],
```


Laminas Events
----------

`api-tools-versioning` provides no new events, but does provide 4 distinct listeners:

#### Laminas\ApiTools\Versioning\PrototypeRouteListener

This listener is attached to `ModuleEvent::EVENT_MERGE_CONFIG`.  It is responsible for iterating the
routes provided in the `api-tools-versioning.uri` configuration to look for corresponding routes in the
`router.routes` configuration.  When a match is detected, this listener will apply the versioning
route match configuration to the route configuration.

#### Laminas\ApiTools\Versioning\VersionListener

This listener is attached to the `MvcEvent::EVENT_ROUTE` at a priority of `-41`.  This listener is
responsible for updating controller service names that utilize a versioned namespace naming scheme.
For example, if the currently matched route provides a controller name such as `Foo\V1\Bar`, and the
currently selected version through URL or media type is `4`, then the controller service name will
be updated in the route matches to `Foo\V4\Bar`;

#### Laminas\ApiTools\Versioning\AcceptListener

This listener is attached to the `MvcEvent::EVENT_ROUTE` at a priority of `-40`. This listener is
responsible for parsing out information from the provided regular expressions (see the
`content-type` configuration key for details) from any `Accept` header that is present in the
request, and assigning that information to the route match, with the regex parameter names as keys.

#### Laminas\ApiTools\Versioning\ContentTypeListener

This listener is attached to the `MvcEvent::EVENT_ROUTE` at a priority of `-40`. This listener is
responsible for parsing out information from the provided regular expressions (see the
`content-type` configuration key for details) from any `Content-Type` header that is present in the
request, and assigning that information to the route match, with the regex parameter names as keys.

Laminas Services
------------

`api-tools-versioning` provides no unique services other than those that serve the purpose
of event listeners, namely:

- `Laminas\ApiTools\Versioning\VersionListener`
- `Laminas\ApiTools\Versioning\AcceptListener`
- `Laminas\ApiTools\Versioning\ContentTypeListener`
