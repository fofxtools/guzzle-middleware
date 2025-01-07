# Test Client Design Document

## Purpose
Create an automated test client to demonstrate and verify GuzzleMiddleware's capabilities, focusing on:
- Multiple transaction handling (redirects)
- Logging functionality 
- Debugging output
- Error scenarios

## Technical Analysis

### Key Components from Existing Codebase
- `MiddlewareClient` - Core class for handling requests
- `makeMiddlewareRequest()` - Function for making requests
- `printOutput()` - Function for output formatting
- Monolog integration for logging

### Test Client Architecture

#### File Structure
```
test-client/
├── src/
│   ├── TestClient.php          # Main test client implementation
│   ├── TestServer.php          # Local test server implementation
│   └── Scenarios/             # Test scenario implementations
├── public/
│   ├── index.php              # Router for test endpoints
│   ├── redirect.php           # Redirect endpoint handler
│   └── error.php             # Error scenario handler
├── config/
│   └── test-endpoints.php     # Local endpoint configurations
└── run.php                    # CLI entry point
```

### Test Scenarios

1. **Basic Transaction Tests**
```php
// Example scenario using local test server
$client->makeRequest('GET', 'http://localhost:8080/api/test');
$client->makeRequest('POST', 'http://localhost:8080/api/echo', ['json' => ['test' => 'data']]);
```

2. **Redirect Chain Tests**
```php
// Example scenarios with local redirects
$client->makeRequest('GET', 'http://localhost:8080/redirect/3');  // 3 redirects
$client->makeRequest('GET', 'http://localhost:8080/redirect/loop');  // Redirect loop test
```

3. **Error Handling Tests**
```php
$client->makeRequest('GET', 'http://localhost:8080/error/404');  // 404 error
$client->makeRequest('GET', 'http://localhost:8080/error/500');  // 500 error
$client->makeRequest('GET', 'http://localhost:8080/timeout');    // Timeout simulation
```

### Local Test Server
- Built-in PHP development server
- Configurable endpoints for different scenarios
- Controlled environment for testing
- Predictable responses and behaviors

### Implementation Phases

#### Phase 1: Core Functionality
- Local test server setup
- Basic request execution
- Redirect handling
- Transaction logging
- Output formatting

#### Phase 2: Error Scenarios
- Local error simulations
- Timeout simulations
- Connection issues
- Rate limiting simulation

#### Phase 3: Advanced Features
- Custom headers
- Different body formats
- Local SSL testing
- Authentication simulation

### Logging Strategy
- Use Monolog for consistent logging
- Log each transaction in the chain
- Include timing and performance metrics
- Capture full request/response details

### Usage Example
```php
// Start local test server
$server = new TestServer();
$server->start();

// Run tests
$client = new TestClient();
$client->runAllTests();

// Specific scenario
$client->runRedirectTests();

// Stop server
$server->stop();
```

### Success Criteria
- Accurately tracks all transactions in redirect chains
- Properly logs request/response cycles
- Handles errors appropriately
- Provides clear debugging output
- Maintains PSR-3 logging compatibility

### Test Endpoints (All Local)
- Basic API endpoints (/api/*)
- Redirect endpoints (/redirect/*)
- Error simulation endpoints (/error/*)
- Timeout simulation endpoints (/timeout)
- Rate limiting endpoints (/ratelimit)

## Next Steps
1. Implement TestServer class for local testing
2. Implement basic TestClient class
3. Create local endpoint handlers
4. Add logging integration
5. Implement redirect chain handling
6. Add error scenario simulations