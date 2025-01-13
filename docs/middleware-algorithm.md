# Guzzle Middleware Client Algorithm

## Key Processes

### Middleware Stack
1. History middleware captures transactions
2. Debug middleware captures timing
3. Request flows through stack
4. Response returns through stack

### Error Mapping
1. Capture exception details
2. Determine appropriate status code
3. Create standardized response
4. Log error details

### Transaction Tracking
1. Capture request details
2. Track request timing
3. Capture response details
4. Store in history container

### Output Generation
1. Format transaction details
2. Apply output options
3. Generate readable output
4. Handle logging if enabled 

## Core Components and Flow

### 1. Initialization
```pseudo
FUNCTION Constructor(config, logger, proxyConfig)
    SET logger = logger ?? new NullLogger()
    SET container = empty array for history
    SET handlerStack = create or use provided
    ADD history middleware to stack
    CONFIGURE client with merged settings
END FUNCTION
```

### 2. Request Handling
```pseudo
FUNCTION makeRequest(method, uri, options)
    START request timing
    CREATE request object
    
    TRY
        SEND request through middleware stack
        CAPTURE timing
        STORE in history container
        RETURN response
    CATCH Exception
        LOG error
        RETURN mapped error response
    END TRY
END FUNCTION
```

### 3. Exception Handling
```pseudo
FUNCTION handleException(exception)
    CAPTURE exception details
    
    IF exception has response
        RETURN original response
    END IF
    
    DETERMINE status code:
        CASE ConnectException: 408
        CASE ClientException: original code or 400
        CASE ServerException: original code or 500
        DEFAULT: 500
    
    CREATE new response with status
    RETURN response
END FUNCTION
```

### 4. Transaction History
```pseudo
FUNCTION getLastTransaction()
    IF container not empty
        GET last transaction
        FORMAT request details
        FORMAT response details
        RETURN formatted output
    END IF
    RETURN empty array
END FUNCTION

FUNCTION getAllTransactions()
    FOR EACH transaction in container
        FORMAT request details
        FORMAT response details
        ADD to output array
    END FOR
    RETURN output array
END FUNCTION
```

### 5. Debug Information
```pseudo
FUNCTION captureDebugInfo(stream, uri)
    IF stream is valid
        READ debug data
        PARSE timing information
        STORE in debug array
    END IF
END FUNCTION
```

### 6. Output Formatting
```pseudo
FUNCTION printOutput(output, options)
    FOR EACH transaction
        FORMAT headers as JSON
        TRUNCATE long content if needed
        APPLY sanitization if needed
        PRINT formatted output
    END FOR
END FUNCTION
```

### 7. Reset/Cleanup
```pseudo
FUNCTION reset(config)
    CLEAR history container
    CLEAR debug information
    CREATE new handler stack
    RECONFIGURE client
END FUNCTION
```

## Data Structures

### Transaction Container
```pseudo
container = [
    {
        request: {
            method: string
            url: string
            headers: array
            body: string
        },
        response: {
            status: integer
            headers: array
            body: string
            timing: float
        }
    }
]
```

### Debug Information
```pseudo
debug = {
    timing: {
        start: float
        end: float
        duration: float
    },
    network: {
        dns: float
        connect: float
        transfer: float
    }
}
```