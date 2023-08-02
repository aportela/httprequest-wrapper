# httprequest-wrapper

This is a simple library to wrap & manage native PHP HTTP requests (there are plenty of serious alternatives like [Guzzle](http://docs.guzzlephp.org/, this is my tiny approach to be used in some of my personal projects, should not be taken too seriously).

## Requirements

- mininum php version 8.x
- curl extension must be enabled

## Limitations

At this time only GET method is supported.

## Install (composer) dependencies:

```
composer require aportela/httprequest-wrapper
```

## Code example:

```
<?php

    require "vendor/autoload.php";

    $logger = new \Psr\Log\NullLogger("");

    $http = new \aportela\HTTPRequestWrapper\HTTPRequest($logger, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1");

    $response = $http->GET("https://packagist.org/packages/aportela/httprequest-wrapper");

    print_r($response);
```

## Response object struct:

    code: HTTP response code
    contentType: response content type
    headers: response headers array
    body: response body contents
