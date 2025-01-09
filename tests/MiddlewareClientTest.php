<?php

declare(strict_types=1);

namespace FOfX\GuzzleMiddleware\Tests;

use FOfX\GuzzleMiddleware\MiddlewareClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Monolog\Level;

class MiddlewareClientTest extends TestCase
{
    private MockHandler $mockHandler;
    private HandlerStack $handlerStack;
    private TestHandler $testHandler;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->mockHandler  = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->testHandler  = new TestHandler();
        $this->logger       = new Logger('test');
        $this->logger->pushHandler($this->testHandler);
    }

    public function testGetDefaultConfig(): void
    {
        $client = new MiddlewareClient([], $this->logger);
        $config = $client->getDefaultConfig();
        $this->assertEquals(5, $config['connect_timeout']);
        $this->assertEquals(10, $config['timeout']);
    }

    public function testGetDefaultConfigWithProxy(): void
    {
        $proxyUrl = 'http://proxy.example.com:8000';
        $client   = new MiddlewareClient([], $this->logger);
        $config   = $client->getDefaultConfig(['proxy' => $proxyUrl]);
        $this->assertArrayHasKey('proxy', $config);
        $this->assertEquals($proxyUrl, $config['proxy']);
    }

    public function testReset(): void
    {
        // Add mock response for the request
        $this->mockHandler->append(new Response(200));

        $config = ['handler' => $this->handlerStack];
        $client = new MiddlewareClient($config, $this->logger);

        // Make request to populate container and debug
        $client->makeRequest('GET', 'http://example.com');

        // Reset without providing handler stack
        $client->reset();

        // Verify container and debug are empty
        $this->assertEmpty($client->getContainer());
        $this->assertEmpty($client->getDebug());

        // Verify client works with default handler
        $response = $client->makeRequest('GET', 'http://example.com');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResetWithNewHandler(): void
    {
        // Add mock response for the request
        $this->mockHandler->append(new Response(200));

        $config = ['handler' => $this->handlerStack];
        $client = new MiddlewareClient($config, $this->logger);

        // Make request to populate container and debug
        $client->makeRequest('GET', 'http://example.com');

        // Verify container and debug are populated
        $this->assertNotEmpty($client->getContainer());
        $this->assertNotEmpty($client->getDebug());

        // Create new handler stack for reset
        $newMockHandler  = new MockHandler([new Response(200)]);
        $newHandlerStack = HandlerStack::create($newMockHandler);

        // Reset the client with new handler stack
        $client->reset($config);

        // Verify container and debug are empty
        $this->assertEmpty($client->getContainer());
        $this->assertEmpty($client->getDebug());

        // Verify client works with new handler
        $response = $client->makeRequest('GET', 'http://example.com');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testProxyConfiguration()
    {
        $proxyUrl = 'http://proxy.example.com:8000';
        $client   = new MiddlewareClient(['proxy' => $proxyUrl], $this->logger);

        $mock = new MockHandler([
            new Response(200, ['X-Proxy-Used' => 'true']),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client       = new MiddlewareClient(['handler' => $handlerStack, 'proxy' => $proxyUrl], $this->logger);

        $response = $client->makeRequest('GET', 'http://example.com');
        $this->assertEquals('true', $response->getHeaderLine('X-Proxy-Used'));
    }

    public function testSendReturnsResponse()
    {
        $this->mockHandler->append(new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'));
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);

        $request  = new Request('GET', 'http://example.com');
        $response = $client->send($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, World', (string)$response->getBody());
        $this->assertEquals('Bar', $response->getHeaderLine('X-Foo'));
    }

    public function testSendWithOptions()
    {
        $this->mockHandler->append(function (RequestInterface $request) {
            $this->assertEquals('test-value', $request->getHeaderLine('X-Test-Header'));

            return new Response(200);
        });

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $request  = new Request('GET', 'http://example.com');
        $response = $client->send($request, ['headers' => ['X-Test-Header' => 'test-value']]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSendPropagatesExceptions()
    {
        $request = new Request('GET', 'http://example.com');
        $this->mockHandler->append(new RequestException('Error Communicating with Server', $request));

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Error Communicating with Server');

        $client->send($request);
    }

    public function testSendRequest()
    {
        $this->mockHandler->append(new Response(200, [], 'Response Body'));

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $request  = new Request('POST', 'http://example.com', ['Content-Type' => 'application/json'], '{"key":"value"}');
        $response = $client->send($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Response Body', (string)$response->getBody());
    }

    public function testGetContainer()
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], '{"key":"value"}'));
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $client->makeRequest('GET', 'http://example.com');

        $container = $client->getContainer();
        $this->assertNotEmpty($container);
        $this->assertArrayHasKey(0, $container);

        // Check request details
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Request::class, $container[0]['request']);
        $this->assertEquals('GET', $container[0]['request']->getMethod());
        $this->assertEquals('http://example.com', (string)$container[0]['request']->getUri());

        // Check response details
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $container[0]['response']);
        $this->assertEquals(200, $container[0]['response']->getStatusCode());
        $this->assertEquals('{"key":"value"}', (string)$container[0]['response']->getBody());
    }

    public function testGetDebugReturnsArray()
    {
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $debug  = $client->getDebug();
        $this->assertIsArray($debug);
    }

    public function testCaptureDebugInfo()
    {
        // Create client
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);

        // Create debug stream with test content
        $debugStream  = fopen('php://temp', 'r+');
        $debugContent = "* Connected to example.com\n> GET / HTTP/1.1\n> Host: example.com";
        fwrite($debugStream, $debugContent);

        // Set up test data directly in debug property
        $uri                = 'http://example.com';
        $reflectionProperty = new \ReflectionProperty($client, 'debug');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($client, [$uri => $debugContent]);

        // Use public getDebug() to verify the debug info
        $debug = $client->getDebug();
        $this->assertArrayHasKey($uri, $debug);
        $this->assertEquals($debugContent, $debug[$uri]);
        $this->assertStringContainsString('Connected to example.com', $debug[$uri]);
        $this->assertStringContainsString('GET / HTTP/1.1', $debug[$uri]);
        $this->assertStringContainsString('Host: example.com', $debug[$uri]);
    }

    public function testCreateRequest()
    {
        $client  = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $request = $client->createRequest('GET', 'http://example.com');
        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('http://example.com', (string)$request->getUri());
    }

    public function testCreateRequestWithOptions()
    {
        $client  = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $options = ['headers' => ['X-Test-Header' => 'test-value'], 'body' => 'test-body'];
        $request = $client->createRequest('GET', 'http://example.com', $options);
        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('http://example.com', (string)$request->getUri());
        $this->assertEquals('test-value', $request->getHeaderLine('X-Test-Header'));
        $this->assertEquals('test-body', (string)$request->getBody());
    }

    public function testMakeRequestSuccess()
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client       = new MiddlewareClient(['handler' => $handlerStack], $this->logger);

        $response = $client->makeRequest('GET', 'http://example.com');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, World', (string)$response->getBody());
        $this->assertEquals('Bar', $response->getHeaderLine('X-Foo'));
    }

    public function testMakeRequestWithOptions()
    {
        $this->mockHandler->append(function ($request) {
            $this->assertEquals('test-value', $request->getHeaderLine('X-Test-Header'));

            return new Response(200);
        });

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $client->makeRequest('GET', 'http://example.com', ['headers' => ['X-Test-Header' => 'test-value']]);
    }

    public function testMakeRequestWithLargeBody()
    {
        $largeBody = str_repeat('LargePayload', 10000);
        $this->mockHandler->append(new Response(200, [], $largeBody));  // Set the large body in the mock response

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $response = $client->makeRequest('POST', 'http://example.com', ['body' => $largeBody]);

        $this->assertEquals(200, $response->getStatusCode());

        // Rewind the response body to ensure we can read it
        $response->getBody()->rewind();

        // Assert the response body matches the large body
        $this->assertEquals($largeBody, (string)$response->getBody());
    }

    public function testMakeRequestErrorHandling()
    {
        $this->mockHandler->append(new Response(500, [], 'Server Error'));

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $response = $client->makeRequest('GET', 'http://example.com');

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Server Error', (string)$response->getBody());

        // Check if error was logged
        $this->assertTrue($this->testHandler->hasErrorRecords(), 'No error records found in log');
    }

    public function testMakeRequestNetworkError()
    {
        $this->mockHandler->append(new RequestException('Network Error', new Request('GET', 'http://example.com')));

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $response = $client->makeRequest('GET', 'http://example.com');

        $logs = array_filter($this->testHandler->getRecords(), function ($record) {
            return str_contains($record->message, 'Request failed') && $record->level === Level::Error;
        });
        $this->assertNotEmpty($logs, 'No error logs found for network error');
    }

    public function testMakeRequestRequestExceptionWithoutResponse()
    {
        $this->mockHandler->append(new RequestException('Error without response', new Request('GET', 'http://example.com')));

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $response = $client->makeRequest('GET', 'http://example.com');

        $this->assertEquals(408, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Error without response"}', (string)$response->getBody());
    }

    public function testHandleException()
    {
        $this->mockHandler->append(new RequestException('Error Communicating with Server', new Request('GET', 'http://example.com')));
        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $response = $client->makeRequest('GET', 'http://example.com');
        $this->assertEquals(408, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Error Communicating with Server"}', (string)$response->getBody());
    }

    public function testGetLastTransaction()
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], '{"key":"value"}'));

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $client->makeRequest('GET', 'http://example.com');
        $output = $client->getLastTransaction();

        $this->assertIsArray($output);
        $this->assertCount(1, $output); // Ensure only 1 transaction is returned
        $this->assertEquals(200, $output[0]['response']['statusCode']);
        $this->assertEquals('{"key":"value"}', $output[0]['response']['body']);
        $this->assertEquals('OK', $output[0]['response']['reasonPhrase']);
        $this->assertEquals('1.1', $output[0]['request']['protocol']);
        $this->assertEquals('/', $output[0]['request']['target']);

        // Check if the headers are valid JSON and contain the expected 'Content-Type'
        $this->assertJson($output[0]['response']['headers']);
        $headers = json_decode($output[0]['response']['headers'], true);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type'][0]);
    }

    public function testGetLastTransactionWithNoTransactions()
    {
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $output = $client->getLastTransaction(); // No transactions made yet

        $this->assertEmpty($output); // Expect an empty output array
    }

    public function testGetAllTransactions()
    {
        $this->mockHandler->append(
            new Response(200, ['X-Test' => 'Value'], 'Test Content')
        );

        $client = new MiddlewareClient([
            'handler' => $this->handlerStack,
        ], $this->logger);

        // Make one request
        $client->makeRequest('GET', 'http://example.com');

        // Get all transactions
        $output = $client->getAllTransactions();

        $this->assertCount(1, $output);
        $this->assertEquals(200, $output[0]['response']['statusCode']);
        $this->assertEquals('Test Content', $output[0]['response']['body']);
        $this->assertEquals('OK', $output[0]['response']['reasonPhrase']);
        $this->assertEquals('1.1', $output[0]['request']['protocol']);
        $this->assertEquals('/', $output[0]['request']['target']);
    }

    public function testPrintLastTransaction()
    {
        $this->mockHandler->append(new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'));
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $client->makeRequest('GET', 'http://example.com');

        ob_start();
        $client->printLastTransaction(true, 1000, true, null, true);
        $output = ob_get_clean();

        // Verify that the logger received the output
        $this->assertTrue(
            $this->testHandler->hasRecordThatContains('Request/Response Details', Level::Info),
            'No log record found for output'
        );
    }

    public function testPrintLastTransactionWithLogger()
    {
        $this->mockHandler->append(new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'));
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $client->makeRequest('GET', 'http://example.com');

        ob_start();
        $client->printLastTransaction(true, 1000, true, null, false);
        $output = ob_get_clean();

        // Verify output was echoed
        $this->assertNotEmpty($output);
        // Verify logger didn't receive the output
        $this->assertFalse(
            $this->testHandler->hasRecordThatContains('Request/Response Details', Level::Info),
            'Unexpected log record found'
        );
    }

    public function testPrintAllTransactions()
    {
        $this->mockHandler->append(
            new Response(200, ['X-Test' => 'Value'], 'Test Content')
        );

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $client->makeRequest('GET', 'http://example.com');

        // First verify the transaction count
        $transactions = $client->getAllTransactions();
        $this->assertCount(1, $transactions, 'Should have exactly one transaction');

        // Then test the printing
        ob_start();
        $client->printAllTransactions(true, 1000, true, null, false);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Test Content', $output);
    }

    public function testWorksWithPsr3Logger(): void
    {
        $logger = new TestLogger();
        $this->mockHandler->append(new Response(200, [], 'Hello World'));

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $logger);
        $client->makeRequest('GET', 'http://example.com');

        $this->assertNotEmpty($logger->logs);
        $this->assertSame('info', $logger->logs[0]['level']);
        $this->assertSame('MiddlewareClient initialized', $logger->logs[0]['message']);

        // Check for the request log
        $requestLog = array_filter($logger->logs, fn ($log) => $log['message'] === 'Starting request');
        $this->assertNotEmpty($requestLog, 'No request log found');
        $this->assertSame('info', reset($requestLog)['level']);
    }

    public function testGetTransactionSummary()
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], '{"key":"value"}'));
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->logger);
        $client->makeRequest('GET', 'http://example.com');
        $summary = $client->getTransactionSummary();
        $this->assertArrayHasKey('request_methods', $summary);
        $this->assertArrayHasKey('request_urls', $summary);
        $this->assertArrayHasKey('request_protocols', $summary);
        $this->assertArrayHasKey('request_targets', $summary);
        $this->assertArrayHasKey('response_status_codes', $summary);
        $this->assertArrayHasKey('response_content_lengths', $summary);
        $this->assertArrayHasKey('response_reason_phrases', $summary);
    }
}
