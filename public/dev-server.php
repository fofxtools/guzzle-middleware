<?php

/**
 * This is a simple development server that will be used to test the GuzzleMiddleware library.
 * It will be used to test the library's ability to handle redirects, logging, and debugging output.
 *
 * To start the server, run the following command:
 * php -S localhost:8000 public/dev-server.php
 *
 * This will start a local server on port 8000 and use dev-server.php as the router for all requests.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use FOfX\Helper;

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

    // Route handling
    if ($path === '/api/test') {
        $sendResponse([
            'status'  => 'ok',
            'message' => 'Basic test endpoint',
        ]);
    }

    if ($path === '/api/echo') {
        $sendResponse([
            'status'  => 'ok',
            'method'  => $_SERVER['REQUEST_METHOD'],
            'headers' => getallheaders(),
            'query'   => $_GET,
            'body'    => file_get_contents('php://input'),
        ]);
    }

    // Simple redirect endpoint
    if (preg_match('#^/redirect/(\d+)$#', $path, $matches)) {
        $count     = (int) $matches[1];
        $nextCount = $count - 1;

        if ($nextCount > 0) {
            header('Location: /redirect/' . $nextCount);
            http_response_code(302);
            exit;
        }

        $sendResponse([
            'status'  => 'ok',
            'message' => 'Redirect chain completed',
        ]);
    }

    // Error endpoint
    if (preg_match('/^\/error\/(\d+)$/', $path, $matches)) {
        $code = (int)$matches[1];
        // Validate code is a valid HTTP status
        if ($code >= 400 && $code < 600) {
            http_response_code($code);
            echo json_encode([
                'status'  => 'error',
                'code'    => $code,
                'message' => 'Error response with code ' . $code,
            ]);
            exit;
        }
    }

    // Delay endpoint
    if (preg_match('/^\/delay\/([\d.]+)$/', $path, $matches)) {
        $seconds = min((float)$matches[1], 30.0);  // Cap at 30 seconds for safety
        Helper\float_sleep($seconds);
        $sendResponse([
            'status'  => 'ok',
            'message' => "Response delayed by {$seconds} seconds",
            'delay'   => $seconds,
        ]);
    }

    // POST endpoint
    if ($path === '/api/post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // Handle JSON
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $sendResponse([
                    'status'  => 'error',
                    'message' => 'Invalid JSON provided',
                ], 400);
            }
        }
        // Handle form data
        elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $input = $_POST;
        }
        // Invalid content type
        else {
            $sendResponse([
                'status'  => 'error',
                'message' => 'Unsupported content type',
            ], 400);
        }

        $sendResponse([
            'status'       => 'ok',
            'data'         => $input ?? null,
            'content_type' => $contentType,
        ]);
    }

    // Upload endpoint
    if ($path === '/api/upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_FILES['file'])) {
            $sendResponse([
                'status'  => 'error',
                'message' => 'No file uploaded',
            ], 400);
        }

        $file = $_FILES['file'];

        // Basic error checking
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $sendResponse([
                'status'  => 'error',
                'message' => 'Upload failed',
                'code'    => $file['error'],
            ], 400);
        }

        $sendResponse([
            'status'  => 'ok',
            'message' => 'File uploaded successfully',
            'file'    => [
                'name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type'],
            ],
        ]);
    }

    // Auth endpoint
    if ($path === '/api/auth') {
        // Get Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Handle Basic Auth
        if (strpos($authHeader, 'Basic ') === 0) {
            $credentials           = base64_decode(substr($authHeader, 6));
            [$username, $password] = explode(':', $credentials);

            if ($username === 'test' && $password === 'password') {
                $sendResponse([
                    'status'  => 'ok',
                    'message' => 'Basic auth successful',
                    'user'    => $username,
                ]);
            }
        }

        // Handle Bearer Token
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);

            if ($token === 'valid-token') {
                $sendResponse([
                    'status'  => 'ok',
                    'message' => 'Token auth successful',
                ]);
            }
        }

        // Auth failed
        $sendResponse([
            'status'  => 'error',
            'message' => 'Authentication failed',
        ], 401);
    }

    // Default 404 response
    $sendResponse([
        'status'  => 'error',
        'message' => 'Not Found',
    ], 404);
}

// Handle the request
handleRequest();
