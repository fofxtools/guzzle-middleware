# Example Method Outputs

This document shows example outputs from the MiddlewareClient's main methods when handling a redirect chain.

## Starting the Development Server
To run these examples against the local development server:
```bash
php -S localhost:8000 public/dev-server.php
```

## Test Case
```php
$response = $client->makeRequest('GET', 'http://localhost:8000/redirect/3');
```

## getAllTransactions()
Shows all requests/responses in the chain:
```php
Array
(
    [0] => Array
        (
            [request] => Array
                (
                    [method] => GET
                    [url] => http://localhost:8000/redirect/3
                    [headers] => {"User-Agent":["GuzzleHttp\/7"],"Host":["localhost:8000"]}
                    [body] => 
                    [protocol] => 1.1
                    [target] => /redirect/3
                )

            [response] => Array
                (
                    [statusCode] => 302
                    [headers] => {"Host":["localhost:8000"],"Date":["Mon, 13 Jan 2025 18:55:51 GMT"],"Connection":["close"],"X-Powered-By":["PHP\/8.3.10"],"Location":["\/redirect\/2"],"Content-type":["text\/html; charset=UTF-8"]}
                    [body] => 
                    [contentLength] => 0
                    [reasonPhrase] => Found
                )

        )

    [1] => Array
        (
            [request] => Array
                (
                    [method] => GET
                    [url] => http://localhost:8000/redirect/2
                    [headers] => {"Host":["localhost:8000"],"User-Agent":["GuzzleHttp\/7"]}
                    [body] => 
                    [protocol] => 1.1
                    [target] => /redirect/2
                )

            [response] => Array
                (
                    [statusCode] => 302
                    [headers] => {"Host":["localhost:8000"],"Date":["Mon, 13 Jan 2025 18:55:51 GMT"],"Connection":["close"],"X-Powered-By":["PHP\/8.3.10"],"Location":["\/redirect\/1"],"Content-type":["text\/html; charset=UTF-8"]}
                    [body] => 
                    [contentLength] => 0
                    [reasonPhrase] => Found
                )

        )

    [2] => Array
        (
            [request] => Array
                (
                    [method] => GET
                    [url] => http://localhost:8000/redirect/1
                    [headers] => {"Host":["localhost:8000"],"User-Agent":["GuzzleHttp\/7"]}
                    [body] => 
                    [protocol] => 1.1
                    [target] => /redirect/1
                )

            [response] => Array
                (
                    [statusCode] => 200
                    [headers] => {"Host":["localhost:8000"],"Date":["Mon, 13 Jan 2025 18:55:51 GMT"],"Connection":["close"],"X-Powered-By":["PHP\/8.3.10"],"Content-Type":["application\/json"],"Content-Length":["52"]}
                    [body] => {"status":"ok","message":"Redirect chain completed"}
                    [contentLength] => 52
                    [reasonPhrase] => OK
                )

        )

)
```

## getLastTransaction()
Returns only the final successful request/response.

Note that elements are named 'request' and 'response' instead of '0', '1', etc.:
```php
Array
(
    [request] => Array
        (
            [method] => GET
            [url] => http://localhost:8000/redirect/1
            [headers] => {"Host":["localhost:8000"],"User-Agent":["GuzzleHttp\/7"]}
            [body] => 
            [protocol] => 1.1
            [target] => /redirect/1
        )

    [response] => Array
        (
            [statusCode] => 200
            [headers] => {"Host":["localhost:8000"],"Date":["Mon, 13 Jan 2025 18:55:51 GMT"],"Connection":["close"],"X-Powered-By":["PHP\/8.3.10"],"Content-Type":["application\/json"],"Content-Length":["52"]}
            [body] => {"status":"ok","message":"Redirect chain completed"}
            [contentLength] => 52
            [reasonPhrase] => OK
        )

)
```

## getTransactionSummary()
Provides a condensed view of the entire chain:
```php
Array
(
    [request_methods] => Array
        (
            [0] => GET
            [1] => GET
            [2] => GET
        )

    [request_urls] => Array
        (
            [0] => http://localhost:8000/redirect/3
            [1] => http://localhost:8000/redirect/2
            [2] => http://localhost:8000/redirect/1
        )

    [request_protocols] => Array
        (
            [0] => 1.1
            [1] => 1.1
            [2] => 1.1
        )

    [request_targets] => Array
        (
            [0] => /redirect/3
            [1] => /redirect/2
            [2] => /redirect/1
        )

    [response_status_codes] => Array
        (
            [0] => 302
            [1] => 302
            [2] => 200
        )

    [response_content_lengths] => Array
        (
            [0] => 0
            [1] => 0
            [2] => 52
        )

    [response_reason_phrases] => Array
        (
            [0] => Found
            [1] => Found
            [2] => OK
        )

)
```

## getDebug()
Returns Guzzle's debug output for the connection:
```php
Array
(
    [http://localhost:8000/redirect/3] => * Hostname localhost was found in DNS cache
*   Trying [::1]:8000...
* Connected to localhost (::1) port 8000
> GET /redirect/1 HTTP/1.1
Host: localhost:8000
User-Agent: GuzzleHttp/7

* Request completely sent off
< HTTP/1.1 200 OK
< Host: localhost:8000
< Date: Mon, 13 Jan 2025 18:55:51 GMT
< Connection: close
< X-Powered-By: PHP/8.3.10
< Content-Type: application/json
< Content-Length: 52
< 
* Closing connection
ng connection

* Closing connection

)
```