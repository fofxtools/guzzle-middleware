<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware\MiddlewareClient;

// Create a new MiddlewareClient instance
$client = new MiddlewareClient();

// Make a request to local dev server with 3 redirects
$response = $client->makeRequest('GET', 'http://localhost:8000/redirect/3');

// Print all transactions in a human-readable format
$useLogger = false;
$client->printAllTransactions(useLogger: $useLogger);
