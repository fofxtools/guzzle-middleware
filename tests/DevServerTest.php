<?php

declare(strict_types=1);

namespace FOfX\GuzzleMiddleware\Tests;

use PHPUnit\Framework\TestCase;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use GuzzleHttp\Exception\ConnectException;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class DevServerTest extends TestCase
{
    private ?MiddlewareClient $client = null;
    private TestHandler $testHandler;
    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testHandler = new TestHandler();
        $this->logger      = new Logger('test');
        $this->logger->pushHandler($this->testHandler);

        $config = [
            'base_uri'    => 'http://localhost:8000',
            'http_errors' => false,
            'timeout'     => 2,
        ];

        $this->client = new MiddlewareClient($config, $this->logger);

        // Check if dev server is running
        try {
            $response = $this->client->makeRequest('GET', '/api/test');
            if ($response->getStatusCode() !== 200) {
                $this->markTestSkipped(
                    'Development server returned unexpected status code: ' .
                    $response->getStatusCode()
                );
            }
        } catch (ConnectException $e) {
            $this->markTestSkipped(
                'Development server is not running. Start it with: ' .
                'php -S localhost:8000 public/dev-server.php'
            );
        }

        // Create new client with same config to reset history before each test
        $this->client = new MiddlewareClient($config, $this->logger);
    }

    public function testBasicEndpoint(): void
    {
        $response = $this->client->makeRequest('GET', '/api/test');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('Basic test endpoint', $data['message']);
    }

    public function testEchoEndpoint(): void
    {
        // Test with query parameters
        $response = $this->client->makeRequest('GET', '/api/echo', [
            'query'   => ['foo' => 'bar'],
            'headers' => ['X-Test-Header' => 'test-value'],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('GET', $data['method']);
        $this->assertEquals(['foo' => 'bar'], $data['query']);
        $this->assertArrayHasKey('headers', $data);
        $this->assertEquals('test-value', $data['headers']['X-Test-Header']);
    }

    public function testRedirectEndpoint(): void
    {
        $response = $this->client->makeRequest('GET', '/redirect/2');

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('Redirect chain completed', $data['message']);
    }

    public function testErrorEndpoint(): void
    {
        $client = new MiddlewareClient([], $this->logger);

        // Test 404 error
        $response = $client->makeRequest('GET', 'http://localhost:8000/error/404');
        $this->assertEquals(404, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('error', $body['status']);
        $this->assertEquals(404, $body['code']);
        $this->assertEquals('Error response with code 404', $body['message']);

        // Test 500 error
        $response = $client->makeRequest('GET', 'http://localhost:8000/error/500');
        $this->assertEquals(500, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('error', $body['status']);
        $this->assertEquals(500, $body['code']);

        // Test invalid error code returns 404
        $response = $client->makeRequest('GET', 'http://localhost:8000/error/999');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDelayEndpoint(): void
    {
        // Test float delay (0.5 seconds)
        $startTime = microtime(true);
        $response  = $this->client->makeRequest('GET', '/delay/0.5');
        $endTime   = microtime(true);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $body['status']);
        $this->assertEquals(0.5, $body['delay']);
        $this->assertGreaterThanOrEqual(0.5, $endTime - $startTime);
        $this->assertLessThan(1.0, $endTime - $startTime);
    }

    public function testDelayEndpointTimeout(): void
    {
        // Create client with shorter timeout
        $client = new MiddlewareClient([
            'base_uri' => 'http://localhost:8000',
            'timeout'  => 1,
        ], $this->logger);

        // Request with delay longer than timeout
        $response = $client->makeRequest('GET', '/delay/2');

        // Verify timeout response
        $this->assertEquals(408, $response->getStatusCode());
        $this->assertEquals('Request Time-out', $response->getReasonPhrase());
        $this->assertJson((string)$response->getBody());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('error', $body);
        $this->assertStringContainsString('timed out', $body['error']);
    }

    public function testPostEndpointWithJson(): void
    {
        // Test JSON POST
        $jsonData = ['name' => 'test', 'value' => 123];
        $response = $this->client->makeRequest('POST', '/api/post', [
            'json'    => $jsonData,
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals($jsonData, $data['data']);
    }

    public function testPostEndpointWithFormData(): void
    {
        // Test form POST
        $formData = ['field1' => 'value1', 'field2' => 'value2'];
        $response = $this->client->makeRequest('POST', '/api/post', [
            'form_params' => $formData,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals($formData, $data['data']);
    }

    public function testPostEndpointWithInvalidContentType(): void
    {
        // Test invalid content type
        $response = $this->client->makeRequest('POST', '/api/post', [
            'body'    => 'raw data',
            'headers' => ['Content-Type' => 'text/plain'],
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('error', $data['status']);
    }

    public function testUploadEndpoint(): void
    {
        // Create a test file
        $filename = 'test.txt';
        $content  = 'Test file content';
        $tmpFile  = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($tmpFile, $content);

        // Upload the file
        $response = $this->client->makeRequest('POST', '/api/upload', [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => fopen($tmpFile, 'r'),
                    'filename' => $filename,
                ],
            ],
        ]);

        // Clean up
        unlink($tmpFile);

        // Verify response
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals($filename, $data['file']['name']);
        $this->assertEquals(strlen($content), $data['file']['size']);
        $this->assertEquals('text/plain', $data['file']['type']);
    }

    public function testUploadEndpointWithNoFile(): void
    {
        $response = $this->client->makeRequest('POST', '/api/upload');

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertEquals('No file uploaded', $data['message']);
    }

    public function testBasicAuth(): void
    {
        // Test successful basic auth
        $response = $this->client->makeRequest('GET', '/api/auth', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('test:password'),
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('Basic auth successful', $data['message']);
        $this->assertEquals('test', $data['user']);

        // Test failed basic auth
        $response = $this->client->makeRequest('GET', '/api/auth', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('wrong:wrong'),
            ],
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testTokenAuth(): void
    {
        // Test successful token auth
        $response = $this->client->makeRequest('GET', '/api/auth', [
            'headers' => [
                'Authorization' => 'Bearer valid-token',
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('Token auth successful', $data['message']);

        // Test failed token auth
        $response = $this->client->makeRequest('GET', '/api/auth', [
            'headers' => [
                'Authorization' => 'Bearer invalid-token',
            ],
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testNoAuth(): void
    {
        $response = $this->client->makeRequest('GET', '/api/auth');

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode((string)$response->getBody(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertEquals('Authentication failed', $data['message']);
    }
}
