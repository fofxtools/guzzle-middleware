<?php

require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware\MiddlewareClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

$logger = new Logger('guzzle-middleware');
$logger->pushHandler(new StreamHandler('php://stdout', Level::Info));

// Create a new MiddlewareClient instance
$client = new MiddlewareClient([], $logger);

// Make a request
$response = $client->makeRequest('GET', 'https://www.example.com');

// Print the container and debug info
print_r($client->getContainer());
print_r($client->getDebug());
