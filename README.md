# SightlineApi Symfony Bundle

Symfony Bundle for interfacing with the Sightline APIs.

## What is the SightlineApi Bundle?

SightlineApi is a Symfony bundle to interface with Sightline deployments using REST, Web Services or SOAP.

## Features

SightlineApi supports the following:

- Support for Sightline REST API as a service.
- Support for Sightline Web services API as a service.
- Support for Sightline SOAP API as a service.
- Optional caching of Sightline responses.
- Currently testing with Sightline 9.7 but should work with most 9.x versions and above.

## Requirements

SightlineApi PHP Class requires the following:

- PHP 8.1 or higher
- symfony/http-client
- symfony/cache
- ext/dom
- ext/soap

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
composer require robwdwd/sightline-api-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require sightline-api-bundle
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Robwdwd\SightlineApiBundle\SightlineApiBundle::class => ['all' => true],
];
```

## Configuration

Configuration is done in config/packages/robwdwd_sightline_api.yaml although this can be any filename.

```yaml
sightline_api:
    hostname:   '%env(string:SIGHTLINE_HOSTNAME)%'
    wskey:      '%env(string:SIGHTLINE_WS_KEY)%'
    resttoken:  '%env(string:SIGHTLINE_REST_TOKEN)%'
    username:   '%env(string:SIGHTLINE_SOAP_USERNAME)%'
    password:   '%env(string:SIGHTLINE_SOAP_PASSWORD)%'
    wsdl:       '%env(string:SIGHTLINE_SOAP_WSDL)%'
    cache:      true
    cache_ttl:  300
```

Then in your .env.local (or any other Environment file you wish to use this in) add the following

```ini
SIGHTLINE_HOSTNAME="sp.example.com"
SIGHTLINE_WS_KEY="pieWoojiekoo2oozooneeThi"
SIGHTLINE_REST_TOKEN="Yohmeishuongoh0goeYu9haeph9goh8oogovaeth"
SIGHTLINE_SOAP_USERNAME="user"
SIGHTLINE_SOAP_PASSWORD="Password1234"
SIGHTLINE_SOAP_WSDL="PeakflowSP.wsdl"
```

## Caching

By default the bundle does not cache the responses from Sightline/SP. Setting cache to to true in the
configuration will cache the responses in the cache.app pool. By default it caches the response for
five minutes (300 seconds). You can change this with the cache_ttl config setting.

You can turn on and off the cache in the current instance using the `setShouldCache(bool)` function.
`$restApi->setShouldCache(false)`

If you are using the filesystem cache on your symfony application you will need to manually prune the cache
to remove stale entries from time to time. You can set this up as a cron job.

```console
php bin/console cache:pool:prune
```

## Usage

[Web Services and SOAP](doc/webservices_soap.md)

[REST API](doc/rest.md)
  