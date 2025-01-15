<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a Monolog logger that writes to a file
$logger = new Logger('guzzle-middleware');
// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}
$logger->pushHandler(new StreamHandler($logsDir . '/guzzle-middleware.log', Level::Info));

$url    = 'http://localhost:8000/redirect/3';
$escape = false; // If false, printOutput will not escape HTML

// Create a new MiddlewareClient instance with the logger
$client = new MiddlewareClient([], $logger);

// Make a request
$response = $client->makeRequest('GET', $url);

// Print the transactions (including request and response details)
// This will write to the log file instead of echoing
$client->printAllTransactions(escape: $escape);

echo PHP_EOL . PHP_EOL;

// Alternately, use the standalone functions makeMiddlewareRequest() and printOutput()
// The makeMiddlewareRequest() function will add a user agent to the request
$response = GuzzleMiddleware\makeMiddlewareRequest('GET', $url, [], [], $logger);
GuzzleMiddleware\printOutput(output: $response, escape: $escape, logger: $logger);

// The log file will contain the request/response details
// Check logs/guzzle-middleware.log
