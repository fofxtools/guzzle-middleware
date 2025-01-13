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

use FOfX\GuzzleMiddleware\MiddlewareClient;

$client = new MiddlewareClient();
$response = $client->makeRequest('GET', 'http://httpbin.org/redirect/2');
print_r($client->getAllTransactions());
```

This should print each transaction in the redirect chain:

```php
Array
(
    [0] => Array
        (
            [request] => Array
                (
                    [method] => GET
                    [url] => http://httpbin.org/redirect/2
                    [headers] => {"User-Agent":["GuzzleHttp\/7"],"Host":["httpbin.org"]}
                    [body] => 
                    [protocol] => 1.1
                    [target] => /redirect/2
                )

            [response] => Array
                (
                    [statusCode] => 302
                    [headers] => {"Date":["Mon, 13 Jan 2025 19:03:26 GMT"],"Content-Type":["text\/html; charset=utf-8"],"Content-Length":["247"],"Connection":["keep-alive"],"Server":["gunicorn\/19.9.0"],"Location":["\/relative-redirect\/1"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Credentials":["true"]}
                    [body] => <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<title>Redirecting...</title>
<h1>Redirecting...</h1>
<p>You should be redirected automatically to target URL: <a href="/relative-redirect/1">/relative-redirect/1</a>.  If not click the link.
                    [contentLength] => 247
                    [reasonPhrase] => FOUND
                )

        )

    [1] => Array
        (
            [request] => Array
                (
                    [method] => GET
                    [url] => http://httpbin.org/relative-redirect/1
                    [headers] => {"Host":["httpbin.org"],"User-Agent":["GuzzleHttp\/7"]}
                    [body] => 
                    [protocol] => 1.1
                    [target] => /relative-redirect/1
                )

            [response] => Array
                (
                    [statusCode] => 302
                    [headers] => {"Date":["Mon, 13 Jan 2025 19:03:28 GMT"],"Content-Type":["text\/html; charset=utf-8"],"Content-Length":["0"],"Connection":["keep-alive"],"Server":["gunicorn\/19.9.0"],"Location":["\/get"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Credentials":["true"]}
                    [body] => 
                    [contentLength] => 0
                    [reasonPhrase] => FOUND
                )

        )

    [2] => Array
        (
            [request] => Array
                (
                    [method] => GET
                    [url] => http://httpbin.org/get
                    [headers] => {"Host":["httpbin.org"],"User-Agent":["GuzzleHttp\/7"]}
                    [body] => 
                    [protocol] => 1.1
                    [target] => /get
                )

            [response] => Array
                (
                    [statusCode] => 200
                    [headers] => {"Date":["Mon, 13 Jan 2025 19:03:30 GMT"],"Content-Type":["application\/json"],"Content-Length":["233"],"Connection":["keep-alive"],"Server":["gunicorn\/19.9.0"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Credentials":["true"]}
                    [body] => {
  "args": {}, 
  "headers": {
    "Host": "httpbin.org", 
    "User-Agent": "GuzzleHttp/7", 
    "X-Amzn-Trace-Id": "Root=1-67856380-2722bf0639f803eb79d6fd4b"
  }, 
  "origin": "IP REMOVED", 
  "url": "http://httpbin.org/get"
}

                    [contentLength] => 233
                    [reasonPhrase] => OK
                )

        )

)
```

To print just the last transaction, use the `getLastTransaction` method:

```php
print_r($client->getLastTransaction());
```

This should print the last transaction.

**Note**: The elements are named 'request' and 'response' instead of '0', '1', etc.

```
Array
(
    [request] => Array
        (
            [method] => GET
            [url] => http://httpbin.org/get
            [headers] => {"Host":["httpbin.org"],"User-Agent":["GuzzleHttp\/7"]}
            [body] => 
            [protocol] => 1.1
            [target] => /get
        )

    [response] => Array
        (
            [statusCode] => 200
            [headers] => {"Date":["Mon, 13 Jan 2025 19:03:30 GMT"],"Content-Type":["application\/json"],"Content-Length":["233"],"Connection":["keep-alive"],"Server":["gunicorn\/19.9.0"],"Access-Control-Allow-Origin":["*"],"Access-Control-Allow-Credentials":["true"]}
            [body] => {
  "args": {}, 
  "headers": {
    "Host": "httpbin.org", 
    "User-Agent": "GuzzleHttp/7", 
    "X-Amzn-Trace-Id": "Root=1-67856380-2722bf0639f803eb79d6fd4b"
  }, 
  "origin": "IP REMOVED", 
  "url": "http://httpbin.org/get"
}

            [contentLength] => 233
            [reasonPhrase] => OK
        )

)
```

To print a summary of the transactions, use the `getTransactionSummary` method:

```php
print_r($client->getTransactionSummary());
```

This should print a summary of the transactions:

```
Array
(
    [request_methods] => Array
        (
            [0] => GET
            [1] => GET
            [2] => GET
        )

    [request_urls] => Array
        (
            [0] => http://httpbin.org/redirect/2
            [1] => http://httpbin.org/relative-redirect/1
            [2] => http://httpbin.org/get
        )

    [request_protocols] => Array
        (
            [0] => 1.1
            [1] => 1.1
            [2] => 1.1
        )

    [request_targets] => Array
        (
            [0] => /redirect/2
            [1] => /relative-redirect/1
            [2] => /get
        )

    [response_status_codes] => Array
        (
            [0] => 302
            [1] => 302
            [2] => 200
        )

    [response_content_lengths] => Array
        (
            [0] => 247
            [1] => 0
            [2] => 233
        )

    [response_reason_phrases] => Array
        (
            [0] => FOUND
            [1] => FOUND
            [2] => OK
        )

)
```

To print the Guzzle debug stream, use the `getDebug` method:

```php
print_r($client->getDebug());
```

This should print the following:

```
Array
(
    [http://httpbin.org/redirect/2] => * Found bundle for host: 0x1ef4ffa2960 [serially]
* Re-using existing connection with host httpbin.org
> GET /get HTTP/1.1
Host: httpbin.org
User-Agent: GuzzleHttp/7

* Request completely sent off
< HTTP/1.1 200 OK
< Date: Mon, 13 Jan 2025 19:03:30 GMT
< Content-Type: application/json
< Content-Length: 233
< Connection: keep-alive
< Server: gunicorn/19.9.0
< Access-Control-Allow-Origin: *
< Access-Control-Allow-Credentials: true
< 
* Connection #0 to host httpbin.org left intact
nection #0 to host httpbin.org left intact
edentials: true
< 
* Connection #0 to host httpbin.org left intact

)
```

### Using the `makeMiddlewareRequest` Function

Alternatively, you can use the `makeMiddlewareRequest` function for an alternative approach. See [examples/makeMiddlewareRequest.php](examples/makeMiddlewareRequest.php).

```php
require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create Monolog logger with stdout handler
$logger = new Logger('guzzle-middleware');
$logger->pushHandler(new StreamHandler('php://stdout', Level::Info));

// Create a new MiddlewareClient instance with logger
$client = new MiddlewareClient([], $logger);

$config = [];
$options = [];
$rotateUserAgent = false;

// Make a request to local dev server
$result = GuzzleMiddleware\makeMiddlewareRequest('GET', 'http://httpbin.org/redirect/2', $config, $options, $logger, $rotateUserAgent);
print_r($result);
```

## Documentation

Detailed documentation can be found in the `docs` folder:

- [Example Outputs](docs/example-outputs.md) - Shows sample outputs from key methods like getAllTransactions(), getLastTransaction(), getTransactionSummary(), and getDebug() when handling redirect chains
- [Middleware Algorithm](docs/middleware-algorithm.md) - Explains the algorithm used to handle redirects and other middleware
- [Middleware Flow](docs/middleware-flow.mmd) - A sequence diagram showing the flow of the MiddlewareClient
- [Middleware Structure](docs/middleware-structure.md) - A diagram showing the structure of the MiddlewareClient

## Configuration Options

The `MiddlewareClient` constructor accepts the following parameters:

- `$config` (array): Guzzle configuration options
- `$logger` (LoggerInterface): A PSR-3 compatible logger
- `$proxyConfig` (array): Proxy configuration options

### Proxy Configuration

To use a proxy with the MiddlewareClient:

```php
$proxyConfig = ['proxy' => 'http://proxy.example.com:8000'];
$client = new MiddlewareClient(proxyConfig: $proxyConfig);
```

## Development Server

The package includes a development server for testing and development. Start it before running tests:

```bash
php -S localhost:8000 public/dev-server.php
```

The dev server provides endpoints for testing:
- `/redirect/{n}` - Redirects n times
- `/error/{code}` - Returns specified HTTP error code
- `/delay/{seconds}` - Delays response
- `/api/test` - Basic test endpoint
- `/api/echo` - Echoes request details

Note: The dev server must be running for unit tests to pass.

## Testing and Development

Remember to start the development server before running tests.

To run the PHPUnit test suite through composer:

```bash
composer test
```

To use PHPStan for static analysis:

```bash
composer phpstan
```

To use PHP-CS-Fixer for code style:

```bash
composer cs-fix
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [fofx](https://github.com/fofxtools)

This package is built on top of [Guzzle](https://github.com/guzzle/guzzle), a PHP HTTP client library.