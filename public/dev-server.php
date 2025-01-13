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
        $body = json_encode($data);
        header('Content-Length: ' . strlen($body));
        echo $body;
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

    // Rate limit endpoint
    if ($path === '/api/ratelimit') {
        $storageFile = sys_get_temp_dir() . '/rate_limits.json';
        $ip          = $_SERVER['REMOTE_ADDR'];
        $now         = time();

        // Load current limits
        $limits = file_exists($storageFile)
            ? json_decode(file_get_contents($storageFile), true)
            : [];

        // Clean old entries
        foreach ($limits as $key => $data) {
            if ($data['window_start'] < ($now - 60)) {
                unset($limits[$key]);
            }
        }

        // Get/initialize current IP data
        $current = $limits[$ip] ?? [
            'requests'     => 0,
            'window_start' => $now,
        ];

        // Reset if window expired
        if ($current['window_start'] < ($now - 60)) {
            $current = [
                'requests'     => 0,
                'window_start' => $now,
            ];
        }

        // Check limit
        if ($current['requests'] >= 5) {
            $reset = $current['window_start'] + 60;
            header('X-RateLimit-Limit: 5');
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . $reset);

            $sendResponse([
                'status'      => 'error',
                'message'     => 'Rate limit exceeded',
                'retry_after' => $reset - $now,
            ], 429);
        }

        // Update counters
        $current['requests']++;
        $limits[$ip] = $current;
        file_put_contents($storageFile, json_encode($limits));

        // Add rate limit headers
        header('X-RateLimit-Limit: 5');
        header('X-RateLimit-Remaining: ' . (5 - $current['requests']));
        header('X-RateLimit-Reset: ' . ($current['window_start'] + 60));

        $sendResponse([
            'status'    => 'ok',
            'message'   => 'Request processed',
            'remaining' => 5 - $current['requests'],
        ]);
    }

    // Proxy check endpoint
    if ($path === '/api/proxy-check') {
        $remote_addr       = $_SERVER['REMOTE_ADDR'];
        $x_forwarded_for   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        $x_forwarded_host  = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null;
        $x_forwarded_proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $forwarded         = $_SERVER['HTTP_FORWARDED'] ?? null;
        $via               = $_SERVER['HTTP_VIA'] ?? null;
        $server_port       = $_SERVER['SERVER_PORT'] ?? null;
        $server_protocol   = $_SERVER['SERVER_PROTOCOL'] ?? null;

        $message = "Remote Address: $remote_addr\n";
        $message .= "X-Forwarded-For: $x_forwarded_for\n";
        $message .= "X-Forwarded-Host: $x_forwarded_host\n";
        $message .= "X-Forwarded-Proto: $x_forwarded_proto\n";
        $message .= "Forwarded: $forwarded\n";
        $message .= "Via: $via\n";
        $message .= "Server Port: $server_port\n";
        $message .= "Server Protocol: $server_protocol\n";

        $sendResponse([
            'status'    => 'ok',
            'client_ip' => $remote_addr,
            'headers'   => [
                'x_forwarded_for'   => $x_forwarded_for,
                'x_forwarded_host'  => $x_forwarded_host,
                'x_forwarded_proto' => $x_forwarded_proto,
                'forwarded'         => $forwarded,
                'via'               => $via,
            ],
            'server' => [
                'remote_addr'     => $remote_addr,
                'server_port'     => $server_port,
                'server_protocol' => $server_protocol,
            ],
            'message' => $message,
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
