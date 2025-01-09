<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware\MiddlewareClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create Monolog logger with stdout handler
$logger = new Logger('guzzle-middleware');
$logger->pushHandler(new StreamHandler('php://stdout', Level::Info));

// Create a new MiddlewareClient instance with logger
$client = new MiddlewareClient([], $logger);

// Make a request to local dev server
$response = $client->makeRequest('GET', 'http://localhost:8000/api/test');

// Print the container and debug info
print_r($client->getContainer());
print_r($client->getDebug());
