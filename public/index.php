<?php

require __DIR__ . '/../vendor/autoload.php';

use FOfX\GuzzleMiddleware;
use FOfX\GuzzleMiddleware\MiddlewareClient;

// Create a new MiddlewareClient instance
$client = new MiddlewareClient();

// Make a request
$response = $client->makeRequest('GET', 'https://www.example.com');

// Print the output (including request and response details)
$client->printOutput();

echo PHP_EOL . PHP_EOL;

// Alternately, use the standalone functions makeMiddlewareRequest() and printOutput()
$response = GuzzleMiddleware\makeMiddlewareRequest('GET', 'https://www.example.com');
GuzzleMiddleware\printOutput($response);
