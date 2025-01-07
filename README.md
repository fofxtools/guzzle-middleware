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

- PHP 8.1 or higher
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
require __DIR__ . '/vendor/autoload.php';

use FOfX\GuzzleMiddleware;
use FOfX\GuzzleMiddleware\MiddlewareClient;

$client = new MiddlewareClient();
$response = $client->makeRequest('GET', 'https://www.example.com');
$output = $client->getLastTransaction();
GuzzleMiddleware\printOutput($output);
```

### Using the `makeMiddlewareRequest` Function

Alternatively, you can use the `makeMiddlewareRequest` function for a more streamlined approach:

```php
require __DIR__ . '/vendor/autoload.php';

use FOfX\GuzzleMiddleware;

$output = GuzzleMiddleware\makeMiddlewareRequest('GET', 'https://www.example.com');
GuzzleMiddleware\printOutput($output);
```

In each of these examples, the request and response middleware transaction details are stored in the `$output` array. You can also `print_r()` this array instead of using `printOutput()`.

### Example Output

Both examples above will produce similar output. HTML escaping is by default true, but can be disabled.

The example below creates a Monolog logger and sets it to use stdout. See [examples/monolog-stdout.php](examples/monolog-stdout.php).

However it prints the output using echo rather than the logger, and does not escape HTML:

```php
require __DIR__ . 'vendor/autoload.php';

use FOfX\GuzzleMiddleware;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

$logger = new Logger('guzzle-middleware');
$logger->pushHandler(new StreamHandler('php://stdout', Level::Info));

$escape    = false; // If false, printOutput will not escape HTML
$useLogger = false; // If false, MiddlewareClient\printOutput will use echo instead of the logger
if (!$useLogger) {
    $logger = null;
}

// Create a new MiddlewareClient instance
$client = new MiddlewareClient([], $logger);

// Make a request
$response = $client->makeRequest('GET', 'https://www.example.com');

// Print the output (including request and response details)
$client->printOutput(escape: $escape, useLogger: $useLogger);

echo PHP_EOL . PHP_EOL;

// Alternately, use the standalone functions makeMiddlewareRequest() and printOutput()
$response = GuzzleMiddleware\makeMiddlewareRequest('GET', 'https://www.example.com', [], [], $logger);
GuzzleMiddleware\printOutput(output: $response, escape: $escape, logger: $logger);
```

The output should look like this:

```
[2024-11-13T22:00:46.044290+00:00] guzzle-middleware.INFO: Starting request {"method":"GET","uri":"https://www.example.com"} []
[2024-11-13T22:00:46.465925+00:00] guzzle-middleware.INFO: Request successful {"method":"GET","uri":"https://www.example.com","statusCode":200} []
[2024-11-13T22:00:46.466491+00:00] guzzle-middleware.INFO: Request completed {"method":"GET","uri":"https://www.example.com","duration":0.4200778007507324} []
--------------------------------------------------
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
        "576428"
    ],
    "Cache-Control": [
        "max-age=604800"
    ],
    "Content-Type": [
        "text/html; charset=UTF-8"
    ],
    "Date": [
        "Wed, 13 Nov 2024 22:00:46 GMT"
    ],
    "Etag": [
        "\"3147526947+gzip+ident\""
    ],
    "Expires": [
        "Wed, 20 Nov 2024 22:00:46 GMT"
    ],
    "Last-Modified": [
        "Thu, 17 Oct 2019 07:18:26 GMT"
    ],
    "Server": [
        "ECAcc (nyd/D18F)"
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
* Host www.example.com:443 was resolved.
* IPv6: 2606:2800:21f:cb07:6820:80da:af6b:8b2c
* IPv4: 93.184.215.14
*   Trying 93.184.215.14:443...
* Connected to www.example.com (93.184.215.14) port 443
* ALPN: curl offers http/1.1
*  CAfile: C:\laragon\etc\ssl\cacert.pem
*  CApath: none
* SSL connection using TLSv1.3 / TLS_AES_256_GCM_SHA384 / prime256v1 / RSASSA-PSS
* ALPN: server accepted http/1.1
* Server certificate:
*  subject: C=US; ST=California; L=Los Angeles; O=InternetCorporationforAssignedNamesandNumbers; CN=www.example.org
*  start date: Jan 30 00:00:00 2024 GMT
*  expire date: Mar  1 23:59:59 2025 GMT
*  subjectAltName: host "www.example.com" matched cert's "www.example.com"
*  issuer: C=US; O=DigiCert Inc; CN=DigiCert Global G2 TLS RSA SHA256 2020 CA1
*  SSL certificate verify ok.
*   Certificate level 0: Public key type RSA (2048/112 Bits/secBits), signed using sha256WithRSAEncryption
*   Certificate level 1: Public key type RSA (2048/112 Bits/secBits), signed using sh... [TRUNCATED]
--------------------------------------------------

[2024-11-13T22:00:46.469277+00:00] guzzle-middleware.INFO: Starting request {"method":"GET","uri":"https://www.example.com"} []
[2024-11-13T22:00:46.878952+00:00] guzzle-middleware.INFO: Request successful {"method":"GET","uri":"https://www.example.com","statusCode":200} []
[2024-11-13T22:00:46.879515+00:00] guzzle-middleware.INFO: Request completed {"method":"GET","uri":"https://www.example.com","duration":0.4095311164855957} []
--------------------------------------------------
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
        "Guzzle (Windows NT 10.0; AMD64)"
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
        "530273"
    ],
    "Cache-Control": [
        "max-age=604800"
    ],
    "Content-Type": [
        "text/html; charset=UTF-8"
    ],
    "Date": [
        "Wed, 13 Nov 2024 22:00:47 GMT"
    ],
    "Etag": [
        "\"3147526947+gzip+ident\""
    ],
    "Expires": [
        "Wed, 20 Nov 2024 22:00:47 GMT"
    ],
    "Last-Modified": [
        "Thu, 17 Oct 2019 07:18:26 GMT"
    ],
    "Server": [
        "ECAcc (nyd/D16F)"
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
* Host www.example.com:443 was resolved.
* IPv6: 2606:2800:21f:cb07:6820:80da:af6b:8b2c
* IPv4: 93.184.215.14
*   Trying 93.184.215.14:443...
* Connected to www.example.com (93.184.215.14) port 443
* ALPN: curl offers http/1.1
*  CAfile: C:\laragon\etc\ssl\cacert.pem
*  CApath: none
* SSL connection using TLSv1.3 / TLS_AES_256_GCM_SHA384 / prime256v1 / RSASSA-PSS
* ALPN: server accepted http/1.1
* Server certificate:
*  subject: C=US; ST=California; L=Los Angeles; O=InternetCorporationforAssignedNamesandNumbers; CN=www.example.org
*  start date: Jan 30 00:00:00 2024 GMT
*  expire date: Mar  1 23:59:59 2025 GMT
*  subjectAltName: host "www.example.com" matched cert's "www.example.com"
*  issuer: C=US; O=DigiCert Inc; CN=DigiCert Global G2 TLS RSA SHA256 2020 CA1
*  SSL certificate verify ok.
*   Certificate level 0: Public key type RSA (2048/112 Bits/secBits), signed using sha256WithRSAEncryption
*   Certificate level 1: Public key type RSA (2048/112 Bits/secBits), signed using sh... [TRUNCATED]
--------------------------------------------------
```

### Proxy Configuration

To use a proxy with the MiddlewareClient:

```php
$proxyConfig = ['proxy' => 'http://proxy.example.com:8080'];
$client = new MiddlewareClient(proxyConfig: $proxyConfig);
```

## Configuration Options

The `MiddlewareClient` constructor accepts the following parameters:

- `$config` (array): Guzzle configuration options
- `$logger` (LoggerInterface): A PSR-3 compatible logger
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