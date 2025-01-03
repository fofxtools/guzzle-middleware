<?php

require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

$logger = new Logger('guzzle-middleware');
$logger->pushHandler(new StreamHandler('php://stdout', Level::Info));

$escape    = false; // If false, printOutput will not escape HTML
$useLogger = true; // If false, MiddlewareClient\printOutput will use echo instead of a logger
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
