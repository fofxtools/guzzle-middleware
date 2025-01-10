# Development Server Design Document

## Purpose
This is the plan to create a development server to demonstrate and verify GuzzleMiddleware's capabilities, focusing on:
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

### Development Server Architecture

#### File Structure
```
public/
└── dev-server.php                # Simple procedural endpoint handler
src/
├── MiddlewareClient.php          # Existing core middleware client
└── functions.php                 # Existing helper functions
```

### Starting the Development Server

Start the built-in PHP development server:
```bash
# From project root
php -S localhost:8000 public/dev-server.php
```

This will:
- Start a local server on port 8000
- Use dev-server.php as the router for all requests
- Handle endpoints like /api/test, /redirect/3, etc.

### Test Scenarios

1. **Basic Transaction Tests**
```php
// Example scenario using local test server
use FOfX\GuzzleMiddleware\MiddlewareClient;

$client = new MiddlewareClient();
$client->makeRequest('GET', 'http://localhost:8000/api/test');
$client->makeRequest('POST', 'http://localhost:8000/api/echo', ['json' => ['test' => 'data']]);
```

2. **Redirect Chain Tests**
```php
// Example scenarios with local redirects
$client->makeRequest('GET', 'http://localhost:8000/redirect/3');  // 3 redirects
$client->makeRequest('GET', 'http://localhost:8000/redirect/loop');  // Redirect loop test
```

3. **Error Handling Tests**
```php
$client->makeRequest('GET', 'http://localhost:8000/error/404');  // 404 error
$client->makeRequest('GET', 'http://localhost:8000/error/500');  // 500 error
$client->makeRequest('GET', 'http://localhost:8000/timeout');    // Timeout simulation
```

### Local Development Server
- Built-in PHP development server
- Configurable endpoints for different scenarios
- Controlled environment for testing
- Predictable responses and behaviors

### Implementation Phases

#### Phase 1: Core Functionality
- Local development server setup
- Basic request execution
- Redirect handling
- Transaction logging
- Output formatting

#### Phase 2: Error Scenarios
- Local error simulations
- Timeout simulations
- Connection issues
- Rate limiting simulation

#### Phase 3: Advanced Features (Optional)
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
// Start local development server
use FOfX\GuzzleMiddleware\MiddlewareClient;

// Create client and run tests
$client = new MiddlewareClient();
$client->makeRequest('GET', 'http://localhost:8000/api/test');

// Test redirect scenario
$client->makeRequest('GET', 'http://localhost:8000/redirect/3');
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
1. Create dev-server.php with basic endpoints
2. Implement redirect handling
3. Add error scenario endpoints
4. Add logging integration
5. Test with MiddlewareClient