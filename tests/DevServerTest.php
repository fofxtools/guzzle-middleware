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
                'php -S localhost:8000 src/dev-server.php'
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
}
