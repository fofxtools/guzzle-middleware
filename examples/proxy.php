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

// Will need local proxy server running to access localhost
// Replace with your proxy details
$proxyIp     = '';
$proxyPort   = '';
$proxyConfig = [
    'proxy' => 'http://' . $proxyIp . ':' . $proxyPort,
    // Optional auth if needed:
    // 'proxy_auth' => 'username:password'
];

// Create client with proxy config
$client = new MiddlewareClient(proxyConfig: $proxyConfig, logger: $logger);

$response = $client->makeRequest('GET', 'http://localhost:8000/api/proxy-check');

// Print the transactions, last transaction, transaction summary, and debug info
print_r($client->getAllTransactions());
print_r($client->getLastTransaction());
print_r($client->getTransactionSummary());
print_r($client->getDebug());
