<?php

declare(strict_types=1);

namespace FOfX\GuzzleMiddleware\Tests;

use PHPUnit\Framework\TestCase;
use FOfX\GuzzleMiddleware\MiddlewareClient;
use GuzzleHttp\Exception\ConnectException;

class DevServerTest extends TestCase
{
    private ?MiddlewareClient $client = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new MiddlewareClient([
            'base_uri'    => 'http://localhost:8000',
            'http_errors' => false,
            'timeout'     => 1,
        ]);

        // Check if dev server is running
        try {
            $response = $this->client->makeRequest('GET', '/api/test');
            if ($response->getStatusCode() !== 200) {
                $this->markTestSkipped('Development server returned unexpected status code');
            }
        } catch (ConnectException $e) {
            $this->markTestSkipped('Development server is not running. Start it with: php -S localhost:8000 src/dev-server.php');
        }
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
}
