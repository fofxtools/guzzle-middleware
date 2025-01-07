<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use Psr\Log\NullLogger;

// Optionally create a logger instance
// For logging, you'll need a PSR-3 compatible logger like:
// - Monolog (composer require monolog/monolog)
// - Your framework's logger (e.g. Laravel's Log)
// Or use NullLogger for no logging:
$logger    = new NullLogger();
$escape    = false; // If false, printOutput will not escape HTML
$useLogger = false; // If false, MiddlewareClient\printOutput will use echo instead of the logger
/** @phpstan-ignore-next-line */
if (!$useLogger) {
    $logger = null;
}

// Create a new MiddlewareClient instance
$client = new MiddlewareClient([], $logger);

// Make a request
$response = $client->makeRequest('GET', 'https://www.example.com');

// Print the transactions (including request and response details)
$client->printAllTransactions(escape: $escape, useLogger: $useLogger);

echo PHP_EOL . PHP_EOL;

// Alternately, use the standalone functions makeMiddlewareRequest() and printOutput()
// The makeMiddlewareRequest() function will add a user agent to the request
$response = GuzzleMiddleware\makeMiddlewareRequest('GET', 'https://www.example.com', [], [], $logger);
GuzzleMiddleware\printOutput(output: $response, escape: $escape, logger: $logger);
