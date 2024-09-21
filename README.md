# FOfX Guzzle Middleware

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fofx/guzzle-middleware.svg?style=flat-square)](https://packagist.org/packages/fofx/guzzle-middleware)
[![Total Downloads](https://img.shields.io/packagist/dt/fofx/guzzle-middleware.svg?style=flat-square)](https://packagist.org/packages/fofx/guzzle-middleware)
[![License](https://img.shields.io/packagist/l/fofx/guzzle-middleware.svg?style=flat-square)](https://packagist.org/packages/fofx/guzzle-middleware)

FOfX Guzzle Middleware is an enhanced Guzzle client with middleware, debugging, and proxy support. It provides extended functionality for capturing detailed request and response information, making it easier to debug and log HTTP transactions in your PHP applications.

## Features

- Enhanced Guzzle client with middleware support
- Detailed request and response logging
- Debug information capture
- Proxy configuration support
- Easy-to-use interface for making HTTP requests
- Flexible configuration options

## Requirements

- PHP 8.0 or higher
- Guzzle 7.9 or higher

## Installation

You can install the package via Composer:

```bash
composer require fofx/guzzle-middleware
```

## Usage

### Basic Usage

Here's a simple example of how to use the `MiddlewareClient`:

```php
use FOfX\GuzzleMiddleware\MiddlewareClient;

$client = new MiddlewareClient();
$response = $client->makeRequest('GET', 'https://www.example.com');
$output = $client->getOutput();
printOutput($output);
```

### Using the `makeMiddlewareRequest` Function

Alternatively, you can use the `makeMiddlewareRequest` function for a more streamlined approach:

```php
use function FOfX\GuzzleMiddleware\makeMiddlewareRequest;
use function FOfX\GuzzleMiddleware\printOutput;

$output = makeMiddlewareRequest('GET', 'https://www.example.com');
printOutput($output);
```

In each of these examples, the request and response middleware transaction details are stored in the `$output` array.

### Example Output

Both examples above will produce similar output. Here's an example of what you might see. Escaping is by default true, this is without escaping:

```
Request:
--------------------------------------------------
  Method: GET
  URL: https://www.example.com
--------------------------------------------------
  Headers:
--------------------------------------------------
{
    "Host": [
        "www.example.com"
    ],
    "User-Agent": [
        "Mozilla/5.0 (X11; Linux x86_64)"
    ],
    "Accept-Language": [
        "en-US,en;q=1.0"
    ],
    "Connection": [
        "keep-alive"
    ],
    "Cache-Control": [
        "no-cache"
    ]
}
--------------------------------------------------
--------------------------------------------------
Response:
--------------------------------------------------
  Status Code: 200
--------------------------------------------------
  Headers:
--------------------------------------------------
{
    "Age": [
        "192088"
    ],
    "Cache-Control": [
        "max-age=604800"
    ],
    "Content-Type": [
        "text/html; charset=UTF-8"
    ],
    "Date": [
        "Sat, 21 Sep 2024 10:54:59 GMT"
    ],
    "Etag": [
        "\"3147526947+ident\""
    ],
    "Expires": [
        "Sat, 28 Sep 2024 10:54:59 GMT"
    ],
    "Last-Modified": [
        "Thu, 17 Oct 2019 07:18:26 GMT"
    ],
    "Server": [
        "ECAcc (nyd/D14F)"
    ],
    "Vary": [
        "Accept-Encoding"
    ],
    "X-Cache": [
        "HIT"
    ],
    "Content-Length": [
        "1256"
    ]
}
--------------------------------------------------
  Body:
--------------------------------------------------
<!doctype html>
<html>
<head>
    <title>Example Domain</title>

    <meta charset="utf-8" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style type="text/css">
    body {
        background-color: #f0f0f2;
        margin: 0;
        padding: 0;
        font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
        
    }
    div {
        width: 600px;
        margin: 5em auto;
        padding: 2em;
        background-color: #fdfdff;
        border-radius: 0.5em;
        box-shadow: 2px 3px 7px 2px rgba(0,0,0,0.02);
    }
    a:link, a:visited {
        color: #38488f;
        text-decoration: none;
    }
    @media (max-width: 700px) {
        div {
            margin: 0 auto;
            width: auto;
        }
    }
    </style>    
</head>

<body>
<div>
    <h1>Example Domain</h1>
    <p>This domai... [TRUNCATED]
--------------------------------------------------
Debug Info:
--------------------------------------------------
*   Trying 93.184.215.14:443...
* Connected to www.example.com (93.184.215.14) port 443 (#0)
* ALPN, offering http/1.1
* successfully set certificate verify locations:
*  CAfile: C:\laragon\etc\ssl\cacert.pem
*  CApath: none
* SSL connection using TLSv1.3 / TLS_AES_256_GCM_SHA384
* ALPN, server accepted to use http/1.1
* Server certificate:
*  subject: C=US; ST=California; L=Los Angeles; O=Internet Corporation for Assigned Names and Numbers; CN=www.example.org
*  start date: Jan 30 00:00:00 2024 GMT
*  expire date: Mar  1 23:59:59 2025 GMT
*  subjectAltName: host "www.example.com" matched cert's "www.example.com"
*  issuer: C=US; O=DigiCert Inc; CN=DigiCert Global G2 TLS RSA SHA256 2020 CA1
*  SSL certificate verify ok.
> GET / HTTP/1.1
Host: www.example.com
User-Agent: Mozilla/5.0 (X11; Linux x86_64)
Accept-Language: en-US,en;q=1.0
Connection: keep-alive
Cache-Control: no-cache

* old SSL session ID is stale, removing
* Mark bundle as not supporting multiuse
< HTTP/1.1 200 OK
... [TRUNCATED]
--------------------------------------------------
```

### Proxy Configuration

To use a proxy with the MiddlewareClient:

```php
$proxyConfig = ['proxy' => 'http://proxy.example.com:8080'];
$client = new MiddlewareClient([], null, $proxyConfig);
```

## Configuration Options

The `MiddlewareClient` constructor accepts the following parameters:

- `$config` (array): Guzzle configuration options
- `$logger` (LoggerInterface): A PSR-3 compatible logger instance
- `$proxyConfig` (array): Proxy configuration options

## Testing

To run the PHPUnit test suite through composer:

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [fofx](https://github.com/fofxtools)

This package is built on top of [Guzzle](https://github.com/guzzle/guzzle), a PHP HTTP client library.