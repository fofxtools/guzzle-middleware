<?php

declare(strict_types=1);

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

$config          = [];
$options         = [];
$rotateUserAgent = false;

// Make a request to local dev server
$response = GuzzleMiddleware\makeMiddlewareRequest('GET', 'http://httpbin.org/redirect/2', $config, $options, $logger, $rotateUserAgent);
print_r($response);
