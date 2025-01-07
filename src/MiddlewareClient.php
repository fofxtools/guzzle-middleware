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
 */

declare(strict_types=1);

namespace FOfX\GuzzleMiddleware;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestFactoryInterface;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use FOfX\Helper;

/**
 * Class MiddlewareClient
 *
 * Extends GuzzleHttp\Client to add middleware for logging request/response transactions.
 * It captures transaction history for each request, supporting debugging and error handling.
 *
 * Basic usage example:
 * ```php
 * use FOfX\GuzzleMiddleware\MiddlewareClient;
 * use Psr\Log\LoggerInterface;
 *
 * // Create a new MiddlewareClient instance
 * $client = new MiddlewareClient();  // Uses default PSR-3 NullLogger
 * // Or with custom logger:
 * $customLogger = YourPsrLogger::create(); // Any PSR-3 compatible logger
 * $client = new MiddlewareClient([], $customLogger);
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
 */
class MiddlewareClient
{
    private ClientInterface             $client;
    private array                       $debug     = [];
    private array                       $container = [];
    private HandlerStack                $stack;
    private LoggerInterface             $logger;
    private RequestFactoryInterface     $requestFactory;

    /**
     * MiddlewareClient constructor.
     *
     * Initializes the Guzzle client with middleware for transaction history logging.
     *
     * @param array            $config      optional Guzzle configuration array
     * @param ?LoggerInterface $logger      optional PSR-3 Logger instance
     * @param array|null       $proxyConfig optional proxy settings
     */
    public function __construct(array $config = [], ?LoggerInterface $logger = null, ?array $proxyConfig = null)
    {
        $this->logger = $logger ?? new NullLogger();

        // Initialize container first
        if (isset($config['history_container'])) {
            $this->container = &$config['history_container'];
        }

        // Then set up the handler stack
        $this->stack = $config['handler'] ?? HandlerStack::create();

        // Only add history middleware if we're using our own handler stack
        if (!isset($config['handler'])) {
            $this->stack->push(Middleware::history($this->container));
        }

        // Merge default configuration with any proxy settings and passed config
        $config            = Helper\array_merge_recursive_distinct($this->getDefaultConfig($proxyConfig), $config);
        $config['handler'] = $this->stack;

        // Initialize the request factory
        $this->requestFactory = $config['request_factory'] ?? new HttpFactory();

        $this->logger->info('MiddlewareClient initialized', [
            'proxyConfig'  => $proxyConfig,
            'customConfig' => $config,
        ]);

        $this->client = new Client($config);
    }

    /**
     * Get default Guzzle configuration settings.
     *
     * Adds default timeout and optional proxy settings.
     *
     * @param array|null $proxyConfig optional proxy configuration
     *
     * @return array default Guzzle configuration settings
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
     * Sends an HTTP request.
     *
     * This method is an alias for the Guzzle client's send() method.
     *
     * @param RequestInterface $request the request to send
     * @param array            $options additional options for the request
     *
     * @return ResponseInterface the response from the request
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->client->send($request, $options);
    }

    /**
     * Get the container of transactions.
     *
     * @return array the container of transactions
     */
    public function getContainer(): array
    {
        return $this->container;
    }

    /**
     * Get the debug information.
     *
     * @return array the debug information
     */
    public function getDebug(): array
    {
        return $this->debug;
    }

    /**
     * Capture debug information from the debug stream.
     *
     * @param resource $debugStream stream capturing debug data
     * @param string   $uri         the request URI
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
            $this->logger->info('Debug info captured for URI: ' . $uri, ['debugLength' => strlen($debugContent)]);
        } else {
            $this->logger->warning('Failed to read debug stream for URI: ' . $uri);
        }
        fclose($debugStream);
    }

    /**
     * Create a request with optional headers and body.
     *
     * @param string $method  HTTP method (e.g., 'GET', 'POST').
     * @param string $uri     request URI
     * @param array  $options guzzle options, including headers and body
     *
     * @return RequestInterface the created request
     */
    public function createRequest(string $method, string $uri = '', array $options = []): RequestInterface
    {
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

        $this->logger->info('Request created', ['method' => $method, 'uri' => $uri, 'options' => $options]);

        return $request;
    }

    /**
     * Send an HTTP request with optional custom headers and body.
     *
     * @param string $method  HTTP method (e.g., 'GET', 'POST').
     * @param string $uri     request URI
     * @param array  $options guzzle options, including headers and body
     *
     * @return ResponseInterface the HTTP response
     */
    public function makeRequest(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        $this->logger->info('Starting request', ['method' => $method, 'uri' => $uri]);

        // Stream to capture Guzzle's debug output
        $debugStream = fopen('php://temp', 'r+');

        // Merge default and passed options
        $options = Helper\array_merge_recursive_distinct([
            'debug' => $debugStream,
        ], $options);

        $startTime = microtime(true);

        // Use createRequest to create the request object
        $request = $this->createRequest($method, $uri, $options);

        try {
            $response = $this->send($request, $options);

            // Log error for non-2xx responses
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                $this->logger->error('Non-successful response', [
                    'method'       => $method,
                    'uri'          => $uri,
                    'statusCode'   => $response->getStatusCode(),
                    'responseBody' => (string) $response->getBody(),
                ]);
            } else {
                $this->logger->info('Request successful', [
                    'method'     => $method,
                    'uri'        => $uri,
                    'statusCode' => $response->getStatusCode(),
                ]);
            }
        } catch (RequestException | ConnectException $e) {
            $this->logger->error('Request failed', [
                'method'    => $method,
                'uri'       => $uri,
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);
            $response = $this->handleException($e);
        }

        $endTime  = microtime(true);
        $duration = $endTime - $startTime;

        // Capture debug information for the request
        $this->captureDebugInfo($debugStream, $uri);

        // Store the full transaction (request and response)
        $this->container[] = [
            'request'  => $request,
            'response' => $response,
            'duration' => $duration,
        ];

        $this->logger->info('Request completed', [
            'method'   => $method,
            'uri'      => $uri,
            'duration' => $duration,
        ]);

        return $response;
    }

    /**
     * Handle exceptions by logging the error and creating a fallback response.
     *
     * @param \Exception $e the exception to handle
     *
     * @return ResponseInterface fallback HTTP response
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
     * Retrieve the most recent transaction's output.
     *
     * Uses array_key_last() to access the latest transaction since the history might
     * contain redirects or duplicate requests. Always encodes headers as JSON for
     * consistency and readability in debugging and logging.
     *
     * @return array formatted output of the most recent transaction
     */
    public function getLastTransaction(): array
    {
        $this->logger->info('Retrieving output');

        $output = [];

        if (!empty($this->container)) {
            // Get the most recent transaction to avoid duplicates or redirects
            $transaction = $this->container[array_key_last($this->container)];

            $response = $transaction['response'];
            $request  = $transaction['request'];

            $debugInfo = $this->debug[(string) $request->getUri()] ?? null;
            if ($debugInfo !== null) {
                $this->logger->info('Debug info retrieved', ['debugLength' => strlen($debugInfo)]);
            } else {
                $this->logger->info('No debug info available for this request');
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
                'latestStatusCode' => $output[0]['response']['statusCode'],
            ]);
        } else {
            $this->logger->info('No output available');
        }

        return $output;
    }

    /**
     * Wrapper function to call getOutput() and print the output in a human-readable format.
     *
     * @param bool        $truncate  Whether to truncate long outputs (default: true)
     * @param int         $maxLength Maximum length of truncation (default: 1000)
     * @param bool        $escape    Whether to apply htmlspecialchars to sanitize output (default: true)
     * @param string|null $divider   The divider string to separate sections (default: 50 dashes if null)
     * @param bool        $useLogger Whether to use the Monolog logger (default: true)
     */
    public function printLastTransaction(
        bool $truncate = true,
        int $maxLength = 1000,
        bool $escape = true,
        string $divider = null,
        bool $useLogger = true
    ): void {
        // Retrieve the output using getOutput()
        $output = $this->getLastTransaction();

        // Call the standalone printOutput() function
        printOutput($output, $truncate, $maxLength, $escape, $divider, $useLogger ? $this->logger : null);
    }

    /**
     * Retrieve all transactions' output.
     *
     * @return array formatted output of all transactions
     */
    public function getAllTransactions(): array
    {
        $this->logger->info('Retrieving all transactions');

        $output = [];

        foreach ($this->container as $transaction) {
            $response = $transaction['response'];
            $request  = $transaction['request'];

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
        }

        return $output;
    }

    /**
     * Print all transactions in a human-readable format.
     *
     * @param bool        $truncate  Whether to truncate long outputs (default: true)
     * @param int         $maxLength Maximum length of truncation (default: 1000)
     * @param bool        $escape    Whether to apply htmlspecialchars to sanitize output (default: true)
     * @param string|null $divider   The divider string to separate sections (default: 50 dashes if null)
     * @param bool        $useLogger Whether to use the Monolog logger (default: true)
     */
    public function printAllTransactions(
        bool $truncate = true,
        int $maxLength = 1000,
        bool $escape = true,
        string $divider = null,
        bool $useLogger = true
    ): void {
        $output = $this->getAllTransactions();
        printOutput($output, $truncate, $maxLength, $escape, $divider, $useLogger ? $this->logger : null);
    }
}
