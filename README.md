Laminas Versioning
=============

[![Build Status](https://travis-ci.org/laminas-api-tools/api-tools-versioning.png)](https://travis-ci.org/laminas-api-tools/api-tools-versioning)
[![Coverage Status](https://coveralls.io/repos/laminas-api-tools/api-tools-versioning/badge.png?branch=master)](https://coveralls.io/r/laminas-api-tools/api-tools-versioning)

Laminas module for automating versioned URLs and Accept/Content-Type mediatypes.

Enables:

- Prefixing defined routes with an optional `[/v:version]` segment, specifying a
  constraint of digits only for the version parameter, and defining a default
  version of 1. Default can be overridden by modifying `[api-tools-versioning][default_version]`
  in `module.config.php`.
- Matching a default mediatype regular expression of `application/vnd.{api
  name}.v{version}(.{resource})?+json` in both Accept and Content-Type headers.
- Injecting any discovered version parameters into the route matches.


Installation
------------

You can install using:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
```
