<?php

/**
 * FOfX Guzzle Middleware Helper Functions
 *
 * This file contains a collection of helper functions used throughout the
 * FOfX Guzzle Middleware package. These functions provide utility for
 * array manipulation, Guzzle configuration, HTTP requests, user agent generation,
 * and output formatting.
 *
 * Key functions include:
 * - arrayMergeRecursiveDistinct(): Merges arrays recursively with distinct values
 * - getMinimalUserAgent(): Generates a minimal, OS-accurate user agent string
 * - createGuzzleConfig(): Creates default Guzzle configuration
 * - createGuzzleOptions(): Generates default Guzzle options, including user agent rotation
 * - makeMiddlewareRequest(): Executes an HTTP request using the MiddlewareClient
 * - printOutput(): Formats and displays the output from MiddlewareClient or makeMiddlewareRequest
 *
 * These functions are designed to work in conjunction with the MiddlewareClient
 * class to enhance Guzzle's functionality with additional logging, debugging,
 * and configuration capabilities. They provide flexibility in handling HTTP requests,
 * managing user agents, and processing request/response data.
 */

declare(strict_types=1);

namespace FOfX\GuzzleMiddleware;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use FOfX\Helper;

/**
 * Generate a minimal, OS-accurate user agent string.
 *
 * This function creates a user agent string that accurately reflects the
 * operating system and architecture of the current environment, while
 * maintaining a minimal format. It identifies itself as Guzzle
 * to be transparent about the nature of the request.
 *
 * The generated user agent will have the following format:
 * "Guzzle ({OS info}; {architecture})"
 *
 * Examples:
 * - Linux:   "Guzzle (X11; Linux x86_64)"
 * - macOS:   "Guzzle (Macintosh; x86_64 Mac OS X)"
 * - Windows: "Guzzle (Windows NT 10.0; AMD64)"
 *
 * @return string the generated user agent string
 */
function getMinimalUserAgent(): string
{
    $os           = php_uname('s');
    $architecture = php_uname('m');

    switch (true) {
        case stripos($os, 'Linux') !== false:
            return "Guzzle (X11; Linux {$architecture})";
        case stripos($os, 'Darwin') !== false:
            return "Guzzle (Macintosh; {$architecture} Mac OS X)";
        case stripos($os, 'Windows') !== false:
            return 'Guzzle (Windows NT ' . php_uname('r') . "; {$architecture})";
        default:
            return "Guzzle ({$os}; {$architecture})";
    }
}

/**
 * Create default Guzzle configuration settings.
 *
 * @param string|null $proxy Proxy configuration
 *
 * @return array Configuration array
 */
function createGuzzleConfig(?string $proxy = null): array
{
    return $proxy ? ['proxy' => $proxy] : [];
}

/**
 * Create default Guzzle option settings, including user-agent rotation.
 *
 * @param bool $rotateUserAgent Whether to rotate user agents
 *
 * @return array Options array
 */
function createGuzzleOptions(bool $rotateUserAgent = false): array
{
    $userAgents = [
        // Chrome on Windows 10
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
        // Firefox on Windows 10
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
        // Safari on macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Safari/605.1.15',
        // Chrome on macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
        // Edge on Windows 10
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36 Edg/115.0.1901.183',
        // Chrome on Android
        'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Mobile Safari/537.36',
        // Safari on iOS
        'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
    ];

    $defaultUserAgent = getMinimalUserAgent();

    return [
        'headers' => [
            'User-Agent'      => $rotateUserAgent ? $userAgents[array_rand($userAgents)] : $defaultUserAgent,
            'Accept-Language' => 'en-US,en;q=1.0',
            'Connection'      => 'keep-alive',
            'Cache-Control'   => 'no-cache',
        ],
        'connect_timeout' => 5,
        'timeout'         => 10,
    ];
}

/**
 * Helper function to print the output from MiddlewareClient
 *
 * @param array            $output    The output array
 * @param bool             $truncate  Whether to truncate long outputs (default: true)
 * @param int              $maxLength Maximum length of truncation (default: 1000)
 * @param bool             $escape    Whether to apply htmlspecialchars to sanitize output (default: true)
 * @param string           $divider   The divider string to separate sections (default: 50 dashes if null)
 * @param ?LoggerInterface $logger    PSR-3 Logger instance
 */
function printOutput(
    array $output,
    bool $truncate = true,
    int $maxLength = 1000,
    bool $escape = true,
    string $divider = null,
    ?LoggerInterface $logger = null
): void {
    // Set the default divider if it's null
    if ($divider === null) {
        $divider = str_repeat('-', 50);
    }

    $outputString      = PHP_EOL;
    $totalTransactions = count($output);
    // Track the current transaction index
    $currentIndex = 0;

    // If the $output array has string keys, it should be a single request/response transaction
    // rather than an array of transactions. So wrap in an array.
    if (Helper\has_string_keys($output)) {
        $output = [$output];
    }

    foreach ($output as $transaction) {
        // Increment the index for each transaction
        $currentIndex++;
        // Set a fallback value for missing keys
        $fallback = '(N/A)';

        $outputString .= $divider . PHP_EOL;
        $outputString .= 'Request:' . PHP_EOL;
        $outputString .= $divider . PHP_EOL;

        // Check if 'request' key exists and has 'method'
        if (isset($transaction['request']['method']) && !empty($transaction['request']['method'])) {
            $outputString .= "  Method: {$transaction['request']['method']}" . PHP_EOL;
        } else {
            $outputString .= "  Method: $fallback" . PHP_EOL;
        }

        // Check if 'request' key exists and has 'url'
        if (isset($transaction['request']['url']) && !empty($transaction['request']['url'])) {
            $outputString .= "  URL: {$transaction['request']['url']}" . PHP_EOL;
        } else {
            $outputString .= "  URL: $fallback" . PHP_EOL;
        }

        $outputString .= $divider . PHP_EOL;

        // Format and append request headers if present
        if (!empty($transaction['request']['headers'])) {
            $requestHeaders = json_decode($transaction['request']['headers'], true);
            $outputString .= '  Headers:' . PHP_EOL;
            $outputString .= $divider . PHP_EOL;
            $outputString .= json_encode($requestHeaders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }
        $outputString .= $divider . PHP_EOL;

        // Append request body if present
        if (!empty($transaction['request']['body'])) {
            $requestBody = $transaction['request']['body'];
            if ($truncate && strlen($requestBody) > $maxLength) {
                $requestBody = substr($requestBody, 0, $maxLength) . '... [TRUNCATED]';
            }
            $outputString .= '  Body:' . PHP_EOL;
            $outputString .= $divider . PHP_EOL;
            $outputString .= $requestBody . PHP_EOL;
        }
        $outputString .= $divider . PHP_EOL;

        $outputString .= 'Response:' . PHP_EOL;
        $outputString .= $divider . PHP_EOL;

        // Check if 'response' key exists and has 'statusCode'
        if (isset($transaction['response']['statusCode']) && !empty($transaction['response']['statusCode'])) {
            $outputString .= "  Status Code: {$transaction['response']['statusCode']}" . PHP_EOL;
        } else {
            $outputString .= "  Status Code: $fallback" . PHP_EOL;
        }

        $outputString .= $divider . PHP_EOL;

        // Format and append response headers if present
        if (!empty($transaction['response']['headers'])) {
            $responseHeaders = json_decode($transaction['response']['headers'], true);
            $outputString .= '  Headers:' . PHP_EOL;
            $outputString .= $divider . PHP_EOL;
            $outputString .= json_encode($responseHeaders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }
        $outputString .= $divider . PHP_EOL;

        // Handle response body with optional truncation if present
        if (!empty($transaction['response']['body'])) {
            $responseBody = $transaction['response']['body'];
            if ($truncate && strlen($responseBody) > $maxLength) {
                $responseBody = substr($responseBody, 0, $maxLength) . '... [TRUNCATED]';
            }
            $outputString .= '  Body:' . PHP_EOL;
            $outputString .= $divider . PHP_EOL;
            $outputString .= $responseBody . PHP_EOL;
        }
        $outputString .= $divider . PHP_EOL;

        // Optional debug info with truncation
        if (isset($transaction['debug'])) {
            $debugInfo = $transaction['debug'];
            if ($truncate && strlen($debugInfo) > $maxLength) {
                $debugInfo = substr($debugInfo, 0, $maxLength) . '... [TRUNCATED]';
            }
            $outputString .= 'Debug Info:' . PHP_EOL;
            $outputString .= $divider . PHP_EOL;
            $outputString .= $debugInfo . PHP_EOL;
            $outputString .= $divider . PHP_EOL;
        }

        // Add a divider between transactions only if it's not the last one
        if ($currentIndex < $totalTransactions) {
            $outputString .= $divider . PHP_EOL . PHP_EOL;
        }
    }

    // Sanitize the final output if escape is true
    if ($escape) {
        $outputString = htmlspecialchars($outputString);
    }

    // If no logger is provided, or if the logger is a NullLogger, fall back to echo
    if ($logger === null || $logger instanceof NullLogger) {
        echo $outputString;
    } else {
        // Trim the output for logging
        $logger->info('Request/Response Details:', [
            'output' => trim($outputString),
        ]);
    }
}

/**
 * Make a request using the MiddlewareClient.
 *
 * Note on the logger parameter: In PHP, when an object (like a PSR-3 Logger
 * implementation) is passed to a function, the function receives a copy of the
 * object handle, not a copy of the object itself. This means that while the
 * function can't reassign the caller's original variable, it can interact with
 * the object the handle points to.
 *
 * As a result, any logging operations performed by this function or the underlying
 * MiddlewareClient will be reflected in the original logger object,
 * potentially adding new log entries to it.
 *
 * @param string           $method          HTTP method
 * @param string           $uri             URI for the request
 * @param array            $config          Guzzle configuration
 * @param array            $options         Request options
 * @param ?LoggerInterface $logger          PSR-3 Logger instance
 * @param bool             $rotateUserAgent Whether to rotate user agents
 *
 * @throws \GuzzleHttp\Exception\GuzzleException
 *
 * @return array Formatted output of the request
 *
 * @see     createGuzzleConfig
 * @see     createGuzzleOptions
 */
function makeMiddlewareRequest(
    string $method,
    string $uri,
    array $config = [],
    array $options = [],
    ?LoggerInterface $logger = null,
    bool $rotateUserAgent = false
): array {
    $config  = Helper\array_merge_recursive_distinct(createGuzzleConfig(), $config);
    $options = Helper\array_merge_recursive_distinct(createGuzzleOptions($rotateUserAgent), $options);

    $client = new MiddlewareClient($config, $logger);
    $client->makeRequest($method, $uri, $options);

    return $client->getLastTransaction();
}
