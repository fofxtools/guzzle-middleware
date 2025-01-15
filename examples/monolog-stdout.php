<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

$logger = new Logger('guzzle-middleware');
$logger->pushHandler(new StreamHandler('php://stdout', Level::Info));

$url    = 'http://localhost:8000/redirect/3';
$escape = false; // If false, printOutput will not escape HTML

// Create a new MiddlewareClient instance
$client = new MiddlewareClient([], $logger);

// Make a request
$response = $client->makeRequest('GET', $url);

// Print the transactions (including request and response details)
$client->printAllTransactions(escape: $escape);

echo PHP_EOL . PHP_EOL;

// Alternately, use the standalone functions makeMiddlewareRequest() and printOutput()
$response = GuzzleMiddleware\makeMiddlewareRequest('GET', $url, [], [], $logger);
GuzzleMiddleware\printOutput(output: $response, escape: $escape, logger: $logger);
