<?php

namespace FOfX\GuzzleMiddleware\Tests;

use PHPUnit\Framework\TestCase;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use FOfX\GuzzleMiddleware\Tests\Support\TestLogger;

/**
 * Test suite for functions in functions.php.
 */
class FunctionsTest extends TestCase
{
    private MockHandler $mockHandler;
    private HandlerStack $handlerStack;
    private TestLogger $testLogger;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->testLogger = new TestLogger();
    }

    public function testConstructor()
    {
        $client = new MiddlewareClient([], $this->testLogger);
        $this->assertInstanceOf(MiddlewareClient::class, $client);
    }

    /**
     * Test merging associative arrays where later values overwrite earlier ones.
     */
    public function testArrayMergeRecursiveDistinctAssociativeArrayMerge()
    {
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['a' => 3, 'c' => 4];

        $result = \FOfX\GuzzleMiddleware\arrayMergeRecursiveDistinct($array1, $array2);

        $this->assertEquals(['a' => 3, 'b' => 2, 'c' => 4], $result);
    }

    /**
     * Test merging arrays with numeric keys where values are appended.
     */
    public function testArrayMergeRecursiveDistinctNumericKeyMerge()
    {
        $array1 = [1, 2, 3];
        $array2 = [3, 4, 5];

        $result = \FOfX\GuzzleMiddleware\arrayMergeRecursiveDistinct($array1, $array2);

        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    /**
     * Test merging nested arrays recursively.
     */
    public function testArrayMergeRecursiveDistinctNestedArrayMerge()
    {
        $array1 = ['a' => ['x' => 1], 'b' => 2];
        $array2 = ['a' => ['y' => 3], 'c' => 4];

        $result = \FOfX\GuzzleMiddleware\arrayMergeRecursiveDistinct($array1, $array2);

        $this->assertEquals(['a' => ['x' => 1, 'y' => 3], 'b' => 2, 'c' => 4], $result);
    }

    /**
     * Test merging arrays with both numeric and associative keys.
     */
    public function testArrayMergeRecursiveDistinctMixedKeyMerge()
    {
        $array1 = ['a' => 1, 2, 3];
        $array2 = ['a' => 4, 5, 6];

        $result = \FOfX\GuzzleMiddleware\arrayMergeRecursiveDistinct($array1, $array2);

        $this->assertEquals(['a' => 4, 2, 3, 5, 6], $result);
    }

    /**
     * Test merging empty arrays.
     */
    public function testArrayMergeRecursiveDistinctEmptyArrays()
    {
        $array1 = [];
        $array2 = [];

        $result = \FOfX\GuzzleMiddleware\arrayMergeRecursiveDistinct($array1, $array2);

        $this->assertEquals([], $result);
    }

    /**
     * Test merging multiple arrays with varying depths and structures.
     */
    public function testArrayMergeRecursiveDistinctMultipleArrayMerge()
    {
        $array1 = ['a' => ['x' => 1], 'b' => 2];
        $array2 = ['a' => ['y' => 3], 'b' => 3];
        $array3 = ['c' => 5];
        $array4 = ['a' => ['z' => 6], 'c' => 7];

        $result = \FOfX\GuzzleMiddleware\arrayMergeRecursiveDistinct($array1, $array2, $array3, $array4);

        $this->assertEquals(
            [
                'a' => ['x' => 1, 'y' => 3, 'z' => 6],
                'b' => 3,
                'c' => 7,
            ],
            $result
        );
    }

    /**
     * Test merging arrays with conflicting numeric and associative keys.
     */
    public function testArrayMergeRecursiveDistinctConflictingKeys()
    {
        $array1 = ['a' => 1, 2];
        $array2 = ['a' => 3, 4];

        $result = \FOfX\GuzzleMiddleware\arrayMergeRecursiveDistinct($array1, $array2);

        $this->assertEquals(['a' => 3, 2, 4], $result);
    }

    /**
     * Test getMinimalUserAgent.
     */
    public function testGetMinimalUserAgent()
    {
        $userAgent = \FOfX\GuzzleMiddleware\getMinimalUserAgent();

        // Check if the user agent string starts with "Guzzle ("
        $this->assertStringStartsWith('Guzzle (', $userAgent);

        // Check if the user agent string ends with a closing parenthesis
        $this->assertStringEndsWith(')', $userAgent);

        // Check if the user agent contains the OS and architecture information
        $this->assertMatchesRegularExpression('/\((.*?;\s.*?)\)/', $userAgent);

        // Verify that the user agent doesn't contain any newlines or extra spaces
        $this->assertDoesNotMatchRegularExpression('/\s{2,}/', $userAgent);
        $this->assertStringNotContainsString("\n", $userAgent);
    }

    /**
     * Test createGuzzleConfig with and without proxy.
     */
    public function testCreateGuzzleConfig()
    {
        $configWithProxy = \FOfX\GuzzleMiddleware\createGuzzleConfig('http://proxy.example.com');
        $configWithoutProxy = \FOfX\GuzzleMiddleware\createGuzzleConfig();

        $this->assertEquals(['proxy' => 'http://proxy.example.com'], $configWithProxy);
        $this->assertEquals([], $configWithoutProxy);
    }

    /**
     * Test createGuzzleOptions with and without rotating the User-Agent.
     */
    public function testCreateGuzzleOptions()
    {
        $optionsWithoutRotation = \FOfX\GuzzleMiddleware\createGuzzleOptions(false);
        $optionsWithRotation = \FOfX\GuzzleMiddleware\createGuzzleOptions(true);

        $this->assertArrayHasKey('headers', $optionsWithoutRotation);
        $this->assertArrayHasKey('User-Agent', $optionsWithoutRotation['headers']);

        // Check if the User-Agent follows the expected pattern
        $this->assertMatchesRegularExpression('/^Guzzle \(.+\)$/', $optionsWithoutRotation['headers']['User-Agent']);

        $this->assertArrayHasKey('timeout', $optionsWithoutRotation);
        $this->assertEquals(10, $optionsWithoutRotation['timeout']);

        $this->assertArrayHasKey('headers', $optionsWithRotation);
        $this->assertArrayHasKey('User-Agent', $optionsWithRotation['headers']);

        // For rotation, we can't predict the exact User-Agent, so we just check if it's a non-empty string
        $this->assertNotEmpty($optionsWithRotation['headers']['User-Agent']);
    }

    /**
     * Test makeMiddlewareRequest function.
     */
    public function testMakeMiddlewareRequest()
    {
        $mockHandler = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, ['X-Foo' => 'Bar'], 'Hello, World')
        ]);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mockHandler);
        $logger = new \Psr\Log\NullLogger();
        $config = ['handler' => $handlerStack];
        $options = [];

        $output = \FOfX\GuzzleMiddleware\makeMiddlewareRequest('GET', 'http://example.com', $config, $options, $logger, false);

        $this->assertIsArray($output);
        $this->assertCount(1, $output); // Ensure that the output has 1 request
        $this->assertEquals('Hello, World', $output[0]['response']['body']);
        $this->assertEquals(200, $output[0]['response']['statusCode']);

        // Decode the JSON headers to access them as an array
        $headers = json_decode($output[0]['response']['headers'], true);
        $this->assertEquals('Bar', $headers['X-Foo'][0]);
    }

    public function testCreateGuzzleConfigWithoutProxy()
    {
        $config = \FOfX\GuzzleMiddleware\createGuzzleConfig();
        $this->assertEquals([], $config);
    }

    public function testMakeMiddlewareRequestWithEmptyConfigAndOptions()
    {
        $this->mockHandler->append(new Response(200));

        $client = new MiddlewareClient(['handler' => $this->handlerStack], $this->testLogger);
        $output = \FOfX\GuzzleMiddleware\makeMiddlewareRequest('GET', 'http://example.com', [], [], $this->testLogger);

        $this->assertIsArray($output);
        $this->assertCount(1, $output); // Ensure only 1 transaction is returned
        $this->assertEquals(200, $output[0]['response']['statusCode']);
    }

    public function testMakeMiddlewareRequestWithoutLogger()
    {
        // Append a mock response
        $this->mockHandler->append(new Response(200, ['X-Foo' => ['Bar']], 'Response body'));

        // Make the request without a logger
        $output = \FOfX\GuzzleMiddleware\makeMiddlewareRequest('GET', 'http://example.com', ['handler' => $this->handlerStack], []);

        // Ensure output is an array and contains request/response data
        $this->assertIsArray($output);
        $this->assertArrayHasKey('request', $output[0]);
        $this->assertArrayHasKey('response', $output[0]);
        $this->assertArrayHasKey('debug', $output[0]);

        // Decode the request headers JSON string
        $requestHeaders = json_decode($output[0]['request']['headers'], true);

        // Ensure the request headers are correctly parsed as an array
        $this->assertIsArray($requestHeaders);
        $this->assertArrayHasKey('User-Agent', $requestHeaders);

        // Decode the response headers JSON string
        $responseHeaders = json_decode($output[0]['response']['headers'], true);

        // Ensure the response headers are correctly parsed as an array
        $this->assertIsArray($responseHeaders);
        $this->assertArrayHasKey('X-Foo', $responseHeaders);
        $this->assertEquals('Bar', $responseHeaders['X-Foo'][0]);

        $this->assertEquals('Response body', $output[0]['response']['body']);
    }

    public function testMakeMiddlewareRequestWithLogger()
    {
        // Append a mock response
        $this->mockHandler->append(new Response(200, ['X-Foo' => ['Bar']], 'Response body'));

        // Use the TestLogger for logging
        $output = \FOfX\GuzzleMiddleware\makeMiddlewareRequest(
            'GET',
            'http://example.com',
            ['handler' => $this->handlerStack],
            [],
            $this->testLogger
        );

        // Ensure output is an array and contains request/response data
        $this->assertIsArray($output);
        $this->assertArrayHasKey('request', $output[0]);
        $this->assertArrayHasKey('response', $output[0]);
        $this->assertArrayHasKey('debug', $output[0]);

        // Decode the request headers JSON string
        $requestHeaders = json_decode($output[0]['request']['headers'], true);

        // Check specific parts of the request
        $this->assertEquals('GET', $output[0]['request']['method']);
        $this->assertEquals('http://example.com', $output[0]['request']['url']);

        // Ensure the headers are correctly parsed as an array
        $this->assertIsArray($requestHeaders);
        $this->assertArrayHasKey('User-Agent', $requestHeaders);

        // Decode the response headers JSON string
        $responseHeaders = json_decode($output[0]['response']['headers'], true);

        // Ensure the response headers are correctly parsed as an array
        $this->assertIsArray($responseHeaders);
        $this->assertArrayHasKey('X-Foo', $responseHeaders);
        $this->assertEquals('Bar', $responseHeaders['X-Foo'][0]);

        $this->assertEquals('Response body', $output[0]['response']['body']);

        // Check that the logger contains the correct log entries
        $this->assertTrue($this->testLogger->hasLog('Starting request'));
    }

    public function testMakeMiddlewareRequestWithEmptyConfigAndOptionsAndNoLogger()
    {
        // Append a mock response
        $this->mockHandler->append(new Response(200, ['X-Foo' => ['Bar']], 'Response body'));

        // Pass the mock handler to the Guzzle configuration
        $config = ['handler' => $this->handlerStack];

        // Make the request without a logger, passing the mock handler configuration
        $output = \FOfX\GuzzleMiddleware\makeMiddlewareRequest('GET', 'http://example.com', $config, [], null, false);

        // Ensure output is an array and contains request/response data
        $this->assertIsArray($output);
        $this->assertArrayHasKey('request', $output[0]);
        $this->assertArrayHasKey('response', $output[0]);
        $this->assertArrayHasKey('debug', $output[0]);

        // Decode the request headers JSON string
        $requestHeaders = json_decode($output[0]['request']['headers'], true);

        // Check specific parts of the request
        $this->assertEquals('GET', $output[0]['request']['method']);
        $this->assertEquals('http://example.com', $output[0]['request']['url']);
        $this->assertIsArray($requestHeaders);
        $this->assertArrayHasKey('User-Agent', $requestHeaders);

        // Decode the response headers JSON string
        $responseHeaders = json_decode($output[0]['response']['headers'], true);
        $this->assertIsArray($responseHeaders);
        $this->assertArrayHasKey('X-Foo', $responseHeaders);
        $this->assertEquals('Bar', $responseHeaders['X-Foo'][0]);

        $this->assertEquals('Response body', $output[0]['response']['body']);
    }

    public function testMakeMiddlewareRequestWithConfigAndOptions()
    {
        // Append a mock response
        $this->mockHandler->append(new Response(200, ['X-Foo' => ['Bar']], 'Response body'));

        $config = ['timeout' => 10, 'handler' => $this->handlerStack];
        $options = ['headers' => ['User-Agent' => 'TestAgent']];

        $output = \FOfX\GuzzleMiddleware\makeMiddlewareRequest('GET', 'http://example.com', $config, $options);

        // Ensure output is an array and contains request/response data
        $this->assertIsArray($output);
        $this->assertArrayHasKey('request', $output[0]);
        $this->assertArrayHasKey('response', $output[0]);
        $this->assertArrayHasKey('debug', $output[0]);

        // Decode the request headers JSON string
        $requestHeaders = json_decode($output[0]['request']['headers'], true);

        // Check specific parts of the request
        $this->assertEquals('GET', $output[0]['request']['method']);
        $this->assertEquals('http://example.com', $output[0]['request']['url']);

        // Ensure the headers are correctly parsed as an array
        $this->assertIsArray($requestHeaders);
        $this->assertArrayHasKey('User-Agent', $requestHeaders);
        $this->assertEquals('TestAgent', $requestHeaders['User-Agent'][0]);

        $this->assertEquals('', $output[0]['request']['body']);  // No body sent in this case

        // Decode the response headers JSON string
        $responseHeaders = json_decode($output[0]['response']['headers'], true);

        // Ensure the response headers are correctly parsed as an array
        $this->assertIsArray($responseHeaders);
        $this->assertArrayHasKey('X-Foo', $responseHeaders);
        $this->assertEquals('Bar', $responseHeaders['X-Foo'][0]);

        $this->assertEquals('Response body', $output[0]['response']['body']);
    }
}
