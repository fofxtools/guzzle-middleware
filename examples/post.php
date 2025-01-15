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

// Create client with logger
$client = new MiddlewareClient([], $logger);

// Test JSON POST
$jsonData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'message' => 'Testing POST endpoint'
];

echo "\nTesting JSON POST:\n";
$response = $client->makeRequest('POST', 'http://localhost:8000/api/post', [
    'json' => $jsonData,
    'headers' => ['Content-Type' => 'application/json']
]);
print_r($client->getLastTransaction());

// Test Form POST
$formData = [
    'username' => 'testuser',
    'password' => 'secret123',
    'remember' => 'true'
];

echo "\nTesting Form POST:\n";
$response = $client->makeRequest('POST', 'http://localhost:8000/api/post', [
    'form_params' => $formData
]);
print_r($client->getLastTransaction());
