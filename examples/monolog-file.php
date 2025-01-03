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

$escape    = false; // If false, printOutput will not escape HTML
$useLogger = true;  // If true, use the logger instead of echo
if (!$useLogger) {
    $logger = null;
}

// Create a new MiddlewareClient instance with the logger
$client = new MiddlewareClient([], $logger);

// Make a request
$response = $client->makeRequest('GET', 'https://www.example.com');

// Print the output (including request and response details)
// This will write to the log file instead of echoing
$client->printOutput(escape: $escape, useLogger: $useLogger);

echo PHP_EOL . PHP_EOL;

// Alternately, use the standalone functions makeMiddlewareRequest() and printOutput()
// The makeMiddlewareRequest() function will add a user agent to the request
$response = GuzzleMiddleware\makeMiddlewareRequest('GET', 'https://www.example.com', [], [], $logger);
GuzzleMiddleware\printOutput(output: $response, escape: $escape, logger: $logger);

// The log file will contain the request/response details
// Check logs/guzzle-middleware.log
