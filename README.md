# httprequest-wrapper

## Install (composer) dependencies:

```
composer require aportela/httprequest-wrapper
composer require psr/log
```

# Code example:

```
<?php

    require "vendor/autoload.php";

    $logger = new \Psr\Log\NullLogger("");

    $http = new \aportela\HTTPRequestWrapper\HTTPRequest($logger, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1");

    $response = $http->GET("https://packagist.org/packages/aportela/httprequest-wrapper");

    print_r($response);
```

# Response object struct:

    code: HTTP response code
    contentType: response content type
    body: response body contents
