<?php

namespace FOfX\GuzzleMiddleware\Tests;

use FOfX\GuzzleMiddleware\MiddlewareClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use FOfX\GuzzleMiddleware\Tests\Support\TestLogger;

class MiddlewareClientTest extends TestCase
{
    private MockHandler $mockHandler;
    private HandlerStack $handlerStack;
    private TestLogger $testLogger;

    protected function setUp(): void
    {
        $this->mockHandler  = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->testLogger   = new TestLogger();
    }

    public function testConstructor()
    {
        $client = new MiddlewareClient([], $this->testLogger);
        $this->assertInstanceOf(MiddlewareClient::class, $client);
    }

    public function testSendMethodReturnsResponse()
    {
        $this->mockHandler->append(new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'));
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);

        $request  = new Request('GET', 'http://example.com');
        $response = $client->send($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, World', (string)$response->getBody());
        $this->assertEquals('Bar', $response->getHeaderLine('X-Foo'));
    }

    public function testSendMethodWithOptions()
    {
        $this->mockHandler->append(function (RequestInterface $request) {
            $this->assertEquals('test-value', $request->getHeaderLine('X-Test-Header'));

            return new Response(200);
        });

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $request  = new Request('GET', 'http://example.com');
        $response = $client->send($request, ['headers' => ['X-Test-Header' => 'test-value']]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSendMethodPropagatesExceptions()
    {
        $request = new Request('GET', 'http://example.com');
        $this->mockHandler->append(new RequestException('Error Communicating with Server', $request));

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Error Communicating with Server');

        $client->send($request);
    }

    public function testMakeRequestSuccess()
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client       = new MiddlewareClient(['handler' => $handlerStack], $this->testLogger);

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

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $client->makeRequest('GET', 'http://example.com', ['headers' => ['X-Test-Header' => 'test-value']]);
    }

    public function testSendRequest()
    {
        $this->mockHandler->append(new Response(200, [], 'Response Body'));

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $request  = new Request('POST', 'http://example.com', ['Content-Type' => 'application/json'], '{"key":"value"}');
        $response = $client->send($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Response Body', (string)$response->getBody());
    }

    public function testErrorHandling()
    {
        $this->mockHandler->append(new Response(500, [], 'Server Error'));

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $response = $client->makeRequest('GET', 'http://example.com');

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Server Error', (string)$response->getBody());

        // Check if at least one log entry related to error handling exists
        $logs = array_filter($this->testLogger->logs, function ($log) {
            return strpos($log['message'], 'Request failed') !== false && $log['level'] === 'error';
        });
        $this->assertNotEmpty($logs); // Ensure we captured error logs

        // Check the content of the first error log entry
        $log = reset($logs);
        $this->assertEquals('error', $log['level']);
        $this->assertStringContainsString('Request failed', $log['message']);
    }

    public function testNetworkError()
    {
        $this->mockHandler->append(new RequestException('Network Error', new Request('GET', 'http://example.com')));

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $response = $client->makeRequest('GET', 'http://example.com');

        $this->assertEquals(408, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Network Error"}', (string)$response->getBody());

        // Check if at least one log entry related to error handling exists
        $logs = array_filter($this->testLogger->logs, function ($log) {
            return strpos($log['message'], 'Request failed') !== false && $log['level'] === 'error';
        });
        $this->assertNotEmpty($logs); // Ensure we captured error logs

        // Check the content of the first matching error log entry
        $log = reset($logs); // Get the first log entry that matches
        $this->assertEquals('error', $log['level']);
        $this->assertStringContainsString('Request failed', $log['message']);
    }

    public function testGetOutput()
    {
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], '{"key":"value"}'));

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $client->makeRequest('GET', 'http://example.com');
        $output = $client->getOutput();

        $this->assertIsArray($output);
        $this->assertCount(1, $output); // Ensure only 1 transaction is returned
        $this->assertEquals(200, $output[0]['response']['statusCode']); // Updated key for response
        $this->assertEquals('{"key":"value"}', $output[0]['response']['body']); // Updated key for body

        // Check if the headers are valid JSON and contain the expected 'Content-Type'
        $this->assertJson($output[0]['response']['headers']);
        $headers = json_decode($output[0]['response']['headers'], true);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type'][0]);
    }

    public function testProxyConfiguration()
    {
        $proxyUrl = 'http://proxy.example.com:8080';
        $client   = new MiddlewareClient(['proxy' => $proxyUrl], $this->testLogger);

        $mock = new MockHandler([
            new Response(200, ['X-Proxy-Used' => 'true']),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client       = new MiddlewareClient(['handler' => $handlerStack, 'proxy' => $proxyUrl], $this->testLogger);

        $response = $client->makeRequest('GET', 'http://example.com');
        $this->assertEquals('true', $response->getHeaderLine('X-Proxy-Used'));
    }

    public function testDebugCapture()
    {
        $mock = new MockHandler([
            function ($request, $options) {
                // Simulate debug output
                $debugStream = $options['debug'];
                fwrite($debugStream, 'Debug information');

                return new Response(200);
            },
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client       = new MiddlewareClient(['handler' => $handlerStack], $this->testLogger);
        $client->makeRequest('GET', 'http://example.com');
        $output = $client->getOutput();

        $this->assertArrayHasKey('debug', $output[0]);
        $this->assertNotEmpty($output[0]['debug']);
    }

    public function testRedirectHandling()
    {
        $this->mockHandler->append(
            new Response(301, ['Location' => 'http://example.com/redirected']),
            new Response(200, [], 'Redirected Content')
        );

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $client->makeRequest('GET', 'http://example.com');
        $output = $client->getOutput();

        // Ensure the final response is captured
        $this->assertCount(1, $output); // Only the final response should be in the output
        $this->assertEquals(200, $output[0]['response']['statusCode']); // Ensure final response status is 200
        $this->assertEquals('Redirected Content', $output[0]['response']['body']); // Ensure final response body is correct
    }

    public function testRequestExceptionWithoutResponse()
    {
        $this->mockHandler->append(new RequestException('Error without response', new Request('GET', 'http://example.com')));

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $response = $client->makeRequest('GET', 'http://example.com');

        $this->assertEquals(408, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Error without response"}', (string)$response->getBody());
    }

    public function testGetOutputWithNoTransactions()
    {
        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $output = $client->getOutput(); // No transactions made yet

        $this->assertEmpty($output); // Expect an empty output array
    }

    public function testMakeRequestWithLargeBody()
    {
        $largeBody = str_repeat('LargePayload', 10000);
        $this->mockHandler->append(new Response(200, [], $largeBody));  // Set the large body in the mock response

        $client   = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $response = $client->makeRequest('POST', 'http://example.com', ['body' => $largeBody]);

        $this->assertEquals(200, $response->getStatusCode());

        // Rewind the response body to ensure we can read it
        $response->getBody()->rewind();

        // Assert the response body matches the large body
        $this->assertEquals($largeBody, (string)$response->getBody());
    }
}
