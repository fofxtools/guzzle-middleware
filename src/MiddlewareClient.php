<?php

/**
 * Enhanced Guzzle client with middleware and logging capabilities.
 * 
 * This file contains the MiddlewareClient class, which extends the functionality
 * of Guzzle HTTP client by adding middleware for detailed request/response logging
 * and debugging capabilities. It's designed to make HTTP interactions more
 * transparent and easier to debug in PHP applications.
 * 
 * Key features:
 * - Detailed logging of HTTP requests and responses
 * - Support for proxy configuration
 * - Debug information capture
 * - Easy integration with existing Guzzle-based projects
 * 
 * @package  FOfX\GuzzleMiddleware
 */

declare(strict_types=1);

namespace FOfX\GuzzleMiddleware;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use GuzzleHttp\Psr7\HttpFactory;

/**
 * Class MiddlewareClient
 * 
 * Extends GuzzleHttp\Client to add middleware for logging request/response transactions.
 * It captures transaction history for each request, supporting debugging and error handling.
 *
 * Basic usage example:
 * ```php
 * use FOfX\GuzzleMiddleware\MiddlewareClient;
 * use Psr\Log\NullLogger;
 *
 * // Create a new MiddlewareClient instance
 * $client = new MiddlewareClient([], new NullLogger());
 *
 * // Make a request
 * $response = $client->makeRequest('GET', 'https://api.example.com/data');
 *
 * // Get the output (including request and response details)
 * $output = $client->getOutput();
 *
 * // Use the printOutput function to display the results
 * printOutput($output);
 * ```
 *
 * @package  FOfX\GuzzleMiddleware
 */
class MiddlewareClient extends Client
{
    private array                       $debug          = [];
    private array                       $container      = [];
    private HandlerStack                $stack;
    private array                       $output         = [];
    private LoggerInterface             $logger;
    private RequestFactoryInterface     $requestFactory;

    /**
     * MiddlewareClient constructor.
     * 
     * Initializes the Guzzle client with middleware for transaction history logging.
     * 
     * @param  array             $config       Optional Guzzle configuration array.
     * @param  ?LoggerInterface  $logger       Optional PSR-3 logger instance.
     * @param  array|null        $proxyConfig  Optional proxy settings.
     */
    public function __construct(array $config = [], ?LoggerInterface $logger = null, ?array $proxyConfig = null)
    {
        $this->logger         = $logger ?? new \Psr\Log\NullLogger();
        $this->stack          = $config['handler'] ?? HandlerStack::create();
        $this->stack->push(Middleware::history($this->container));

        // Merge default configuration with any proxy settings and passed config
        $config               = arrayMergeRecursiveDistinct($this->getDefaultConfig($proxyConfig), $config);
        $config['handler']    = $this->stack;

        // Initialize the request factory
        $this->requestFactory = $config['request_factory'] ?? new HttpFactory();

        $this->logger->debug('MiddlewareClient initialized', [
            'proxyConfig' => $proxyConfig,
            'customConfig' => $config
        ]);

        parent::__construct($config);
    }

    /**
     * Get default Guzzle configuration settings.
     * 
     * Adds default timeout and optional proxy settings.
     * 
     * @param   array|null  $proxyConfig  Optional proxy configuration.
     *
     * @return  array                     Default Guzzle configuration settings.
     */
    private function getDefaultConfig(?array $proxyConfig = null): array
    {
        $defaultConfig = [
            'connect_timeout' => 5,
            'timeout'         => 10,
        ];

        if ($proxyConfig && isset($proxyConfig['proxy'])) {
            $defaultConfig['proxy'] = $proxyConfig['proxy'];
        }

        return $defaultConfig;
    }

    /**
     * Send an HTTP request with optional custom headers and body.
     * 
     * @param   string             $method   HTTP method (e.g., 'GET', 'POST').
     * @param   string             $uri      Request URI.
     * @param   array              $options  Guzzle options, including headers and body.
     *
     * @return  ResponseInterface            The HTTP response.
     */
    public function makeRequest(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        $this->logger->info('Starting request', ['method' => $method, 'uri' => $uri]);

        // Stream to capture Guzzle's debug output
        $debugStream = fopen('php://temp', 'r+');

        // Merge default and passed options
        $options = arrayMergeRecursiveDistinct([
            'debug' => $debugStream
        ], $options);

        $startTime = microtime(true);

        // Create the request object
        $request = $this->requestFactory->createRequest($method, $uri);

        // Add headers and body to the request if provided
        if (isset($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }
        if (isset($options['body'])) {
            $request = $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($options['body']));
        }

        try {
            $response = $this->send($request, $options);

            // Log error for non-2xx responses
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                $this->logger->error('Non-successful response', [
                    'method' => $method,
                    'uri' => $uri,
                    'statusCode' => $response->getStatusCode(),
                    'responseBody' => (string) $response->getBody()
                ]);
            } else {
                $this->logger->info('Request successful', [
                    'method' => $method,
                    'uri' => $uri,
                    'statusCode' => $response->getStatusCode()
                ]);
            }
        } catch (RequestException | ConnectException $e) {
            $this->logger->error('Request failed', [
                'method' => $method,
                'uri' => $uri,
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
            $response = $this->handleException($e);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Capture debug information for the request
        $this->captureDebugInfo($debugStream, $uri);

        // Store the full transaction (request and response)
        $this->container[] = [
            'request'  => $request,
            'response' => $response,
            'duration' => $duration
        ];

        $this->logger->info('Request completed', [
            'method' => $method,
            'uri' => $uri,
            'duration' => $duration
        ]);

        return $response;
    }

    /**
     * Handle exceptions by logging the error and creating a fallback response.
     * 
     * @param   \Exception         $e  The exception to handle.
     *
     * @return  ResponseInterface      Fallback HTTP response.
     */
    private function handleException(\Exception $e): ResponseInterface
    {
        $context = [
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ];

        if ($e instanceof RequestException) {
            $context['request'] = $e->getRequest();
            if ($e->hasResponse()) {
                $context['response'] = $e->getResponse();
            }
        }

        // Log the error
        $this->logger->error('Request failed', $context);

        return $e instanceof RequestException && $e->hasResponse()
            ? $e->getResponse()
            : new Response(
                408,
                [],
                json_encode(['error' => $e->getMessage()])
            );
    }

    /**
     * Capture debug information from the debug stream.
     * 
     * @param  resource  $debugStream  Stream capturing debug data.
     * @param  string    $uri          The request URI.
     */
    private function captureDebugInfo($debugStream, string $uri): void
    {
        if (!is_resource($debugStream)) {
            $this->logger->warning('Invalid debug stream for URI: ' . $uri);
            return;
        }

        rewind($debugStream);
        $debugContent = stream_get_contents($debugStream);
        if ($debugContent !== false) {
            $this->debug[$uri] = $debugContent;
            $this->logger->debug('Debug info captured for URI: ' . $uri, ['debugLength' => strlen($debugContent)]);
        } else {
            $this->logger->warning('Failed to read debug stream for URI: ' . $uri);
        }
        fclose($debugStream);
    }

    /**
     * Retrieve the most recent transaction's output.
     * 
     * Uses array_key_last() to access the latest transaction since the history might
     * contain redirects or duplicate requests. Always encodes headers as JSON for
     * consistency and readability in debugging and logging.
     *
     * @return  array    Formatted output of the most recent transaction.
     */
    public function getOutput(): array
    {
        $this->logger->debug('Retrieving output');

        $output = [];

        if (!empty($this->container)) {
            // Get the most recent transaction to avoid duplicates or redirects
            $transaction = $this->container[array_key_last($this->container)];

            $response = $transaction['response'];
            $request  = $transaction['request'];

            $debugInfo = $this->debug[(string) $request->getUri()] ?? null;
            if ($debugInfo !== null) {
                $this->logger->debug('Debug info retrieved', ['debugLength' => strlen($debugInfo)]);
            } else {
                $this->logger->debug('No debug info available for this request');
            }

            // Format output for the most recent request/response
            // JSON encode headers for standardization
            // JSON encode response headers
            // Capture any debug information if available
            $output[] = [
                'request' => [
                    'method'  => $request->getMethod(),
                    'url'     => (string) $request->getUri(),
                    'headers' => json_encode($request->getHeaders()),
                    'body'    => (string) $request->getBody(),
                ],
                'response' => [
                    'statusCode'    => $response->getStatusCode(),
                    'headers'       => json_encode($response->getHeaders()),
                    'body'          => (string) $response->getBody(),
                    'contentLength' => (int) $response->getHeaderLine('Content-Length'),
                ],
                'debug' => $this->debug[(string) $request->getUri()] ?? null,
            ];

            $this->logger->info('Output retrieved', [
                'transactionCount' => count($this->container),
                'latestStatusCode' => $output[0]['response']['statusCode']
            ]);
        } else {
            $this->logger->warning('No output available');
        }

        return $output;
    }

    /**
     * Wrapper function to call getOutput() and print the output in a human-readable format.
     *
     * @param  bool         $truncate   Whether to truncate long outputs (default: true)
     * @param  int          $maxLength  Maximum length of truncation (default: 1000)
     * @param  bool         $escape     Whether to apply htmlspecialchars to sanitize output (default: true)
     * @param  string|null  $divider    The divider string to separate sections (default: 50 dashes if null)
     */
    public function printOutput(
        bool $truncate = true,
        int $maxLength = 1000,
        bool $escape = true,
        string $divider = null
    ): void {
        // Retrieve the output using getOutput()
        $output = $this->getOutput();

        // Call the standalone printOutput() function
        printOutput($output, $truncate, $maxLength, $escape, $divider);
    }

    /**
     * Test the printOutput function with truncation.
     */
    public function testPrintOutputWithTruncation()
    {
        $longBody = str_repeat('a', 2000);
        $testOutput = [
            [
                'response' => [
                    'body' => $longBody,
                ],
            ]
        ];

        ob_start();
        \FOfX\GuzzleMiddleware\printOutput($testOutput, true, 1000);
        $output = ob_get_clean();

        $this->assertStringContainsString(str_repeat('a', 1000), $output);
        $this->assertStringContainsString('... [TRUNCATED]', $output);
        $this->assertStringNotContainsString(str_repeat('a', 1001), $output);
    }

    /**
     * Test the printOutput function without truncation.
     */
    public function testPrintOutputWithoutTruncation()
    {
        $longBody = str_repeat('a', 2000);
        $testOutput = [
            [
                'response' => [
                    'body' => $longBody,
                ],
            ]
        ];

        ob_start();
        \FOfX\GuzzleMiddleware\printOutput($testOutput, false);
        $output = ob_get_clean();

        $this->assertStringContainsString($longBody, $output);
        $this->assertStringNotContainsString('... [TRUNCATED]', $output);
    }

    /**
     * Test the printOutput function with custom maxLength.
     */
    public function testPrintOutputWithCustomMaxLength()
    {
        $longBody = str_repeat('a', 2000);
        $testOutput = [
            [
                'response' => [
                    'body' => $longBody,
                ],
            ]
        ];

        ob_start();
        \FOfX\GuzzleMiddleware\printOutput($testOutput, true, 500);
        $output = ob_get_clean();

        $this->assertStringContainsString(str_repeat('a', 500), $output);
        $this->assertStringContainsString('... [TRUNCATED]', $output);
        $this->assertStringNotContainsString(str_repeat('a', 501), $output);
    }

    /**
     * Test the printOutput function with custom divider.
     */
    public function testPrintOutputWithCustomDivider()
    {
        $testOutput = [
            [
                'request' => [
                    'method' => 'GET',
                    'url' => 'http://example.com',
                ],
                'response' => [
                    'statusCode' => 200,
                    'body' => 'Test body',
                ],
            ]
        ];

        $customDivider = '****';

        ob_start();
        \FOfX\GuzzleMiddleware\printOutput($testOutput, true, 1000, true, $customDivider);
        $output = ob_get_clean();

        $this->assertStringContainsString($customDivider, $output);
        $this->assertStringNotContainsString(str_repeat('-', 50), $output);
    }
}
