<?php

/**
 * This is a simple development server that will be used to test the GuzzleMiddleware library.
 * It will be used to test the library's ability to handle redirects, logging, and debugging output.
 *
 * To start the server, run the following command:
 * php -S localhost:8000 src/dev-server.php
 *
 * This will start a local server on port 8000 and use dev-server.php as the router for all requests.
 */

declare(strict_types=1);

// Basic error handling and logging setup
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Simple router function
function handleRequest(): void
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Basic response helper
    $sendResponse = function (array $data, int $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    };

    // Initial basic route
    if ($path === '/api/test') {
        $sendResponse([
            'status'  => 'ok',
            'message' => 'Basic test endpoint',
        ]);
    }

    // Default 404 response
    $sendResponse([
        'status'  => 'error',
        'message' => 'Not Found',
    ], 404);
}

// Handle the request
handleRequest();
