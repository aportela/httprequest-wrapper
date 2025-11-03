# httprequest-wrapper

This is a simple library to wrap & manage native PHP HTTP requests (there are plenty of serious alternatives like [Guzzle](http://docs.guzzlephp.org/), this is my tiny approach to be used in some of my personal projects, should not be taken too seriously).

## Requirements

- mininum php version 8.4
- curl extension must be enabled

## Limitations

At this time only GET/HEAD methods are supported.

## Install (composer) dependencies:

```Shell
composer require aportela/httprequest-wrapper
```

## Code example:

```php
<?php

    require "vendor/autoload.php";

    $logger = new \Psr\Log\NullLogger("");

    // it requires curl extension, otherwise an \aportela\HTTPRequestWrapper\Exception\CurlMissingException is thrown
    $http = new \aportela\HTTPRequestWrapper\HTTPRequest($logger, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1");

    try {
        $response = $http->GET("https://packagist.org/packages/aportela/httprequest-wrapper");
        if ($response->code == 200) {
            echo $response->body . PHP_EOL;
        } else {
            echo "Error getting remote url, http response code: {$response->code}" . PHP_EOL;
        }
    } catch (\aportela\HTTPRequestWrapper\Exception\CurlExecException $e) {
        // this exception is thrown if the curl execution fails and the remote server does not respond (ex: cannot open the connection, the connection has been reset, ...)
        echo "Error executing curl: " . $e->getMessage();
    }
```

## Response object struct:

    code: HTTP response code (int)
    contentType: response content type (string)
    headers: response headers (array)
    body: response body contents (null|string)

![PHP Composer](https://github.com/aportela/httprequest-wrapper/actions/workflows/php.yml/badge.svg)
