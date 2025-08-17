### ApiCall — HTTP client for GEMVC

This document explains how to use `Gemvc\Http\ApiCall` to perform HTTP requests in GEMVC-based applications. It also outlines proposed, backward-compatible enhancements that can be submitted as a pull request to the main GEMVC repository.

The default behavior remains unchanged: `POST` and `PUT` send JSON bodies and set `Content-Type: application/json` automatically.

---

## Key properties
- `public array $header`: Custom headers as `['Header-Name' => 'value']`.
- `public string $method`: HTTP method (defaults to `GET`).
- `public array $data`: Request body for the legacy JSON path.
- `public null|string|array $authorizationHeader`: If a string is provided, legacy logic overwrites the header list with `Authorization: <value>`.
- `public bool|string $responseBody`: Response body string on success, `false` on failure.
- `public int $http_response_code`: HTTP status code.
- `public ?string $error`: cURL error message if any.
- `public array $files`: Optional legacy multipart path.

---

## Public methods (current API)
- `get(string $url, array $queryParams = []): string|false`
- `post(string $url, array $postData = []): string|false` (sends JSON by default)
- `put(string $url, array $putData = []): string|false` (sends JSON by default)

---

## Proposed backward-compatible enhancements (opt-in)
These additions do not change default behavior. They only apply if you call them explicitly.

- `setTimeouts(int $connectTimeout, int $timeout): self`
  - Configure connection and total timeouts in seconds.
- `setSsl(?string $certPath, ?string $keyPath, ?string $caPath = null, bool $verifyPeer = true, int $verifyHost = 2): self`
  - Configure optional client SSL certificate/key and CA bundle.
- `setRetries(int $maxRetries, int $retryDelayMs = 200, array $retryOnHttpCodes = []): self`
  - Enable simple retry with backoff for specific HTTP codes. Defaults remain disabled.
- `retryOnNetworkError(bool $retry): self`
  - Toggle retry on cURL/network error.
- `postForm(string $url, array $fields = []): string|false`
  - Send application/x-www-form-urlencoded body.
- `postMultipart(string $url, array $fields = [], array $files = []): string|false`
  - Send multipart/form-data with files (boundary is set by cURL).
- `postRaw(string $url, string $rawBody, string $contentType): string|false`
  - Send a raw body with a specific Content-Type.

Note: If your project depends on current vendor behavior, these enhancements can be added via a PR to GEMVC or implemented in a local adapter until upstream acceptance.

---

## Usage examples

### 1) GET with query parameters
```php
use Gemvc\Http\ApiCall;

$api = new ApiCall();
$api->header['Accept'] = 'application/json';
$response = $api->get('https://httpbin.org/get', ['q' => 'gemvc']);

if ($response === false) {
    // Inspect error and status code
    var_dump($api->error, $api->http_response_code);
} else {
    $data = json_decode($response, true);
    var_dump($data);
}
```

### 2) POST with JSON body (default behavior)
```php
use Gemvc\Http\ApiCall;

$api = new ApiCall();
$api->header['Accept'] = 'application/json';
$payload = ['name' => 'Alice', 'role' => 'admin'];
$response = $api->post('https://httpbin.org/post', $payload);

if ($response === false) {
    var_dump($api->error);
} else {
    var_dump(json_decode($response, true));
}
```

### 3) PUT with JSON body (default behavior)
```php
use Gemvc\Http\ApiCall;

$api = new ApiCall();
$api->header['Accept'] = 'application/json';
$payload = ['status' => 'active'];
$response = $api->put('https://httpbin.org/put', $payload);
```

### 4) POST application/x-www-form-urlencoded (proposed)
```php
use Gemvc\Http\ApiCall;

$api = (new ApiCall());
$api->header['Accept'] = 'application/json';
$response = $api->postForm('https://httpbin.org/post', [
    'username' => 'alice',
    'password' => 'secret',
]);
```

### 5) POST multipart/form-data with files (proposed)
```php
use Gemvc\Http\ApiCall;

$api = new ApiCall();
$response = $api->postMultipart('https://httpbin.org/post',
    ['folder' => 'uploads'],
    ['file' => __DIR__ . '/example.png']
);
```

### 6) POST a raw body with custom Content-Type (proposed)
```php
use Gemvc\Http\ApiCall;

$api = new ApiCall();
$rawBody = http_build_query(['username' => 'alice', 'password' => 'secret']);
$response = $api->postRaw('https://httpbin.org/post', $rawBody, 'application/x-www-form-urlencoded');
```

### 7) JSON-RPC request (generic)
JSON-RPC is a protocol over JSON. You can send a JSON object or an array of objects (batch). No special client support is required beyond sending JSON.
```php
use Gemvc\Http\ApiCall;

$api = new ApiCall();
$api->header['Accept'] = 'application/json';
$rpc = [
    'jsonrpc' => '2.0',
    'method' => 'Service.Method',
    'params' => ['key' => 'value'],
    'id' => 1,
];
$response = $api->post('https://example.com/json-rpc', $rpc);
```

### 8) Timeouts (proposed)
```php
use Gemvc\Http\ApiCall;

$api = (new ApiCall())
    ->setTimeouts(10, 30); // connect: 10s, total: 30s
$response = $api->get('https://httpbin.org/delay/2');
```

### 9) Retries with backoff (proposed)
```php
use Gemvc\Http\ApiCall;

$api = (new ApiCall())
    ->setRetries(3, 250, [429, 500, 502, 503, 504])
    ->retryOnNetworkError(true);
$response = $api->get('https://httpbin.org/status/503');
```

### 10) Client SSL certificate/key (proposed)
```php
use Gemvc\Http\ApiCall;

$api = (new ApiCall())
    ->setSsl('/path/to/client.crt', '/path/to/client.key', null, true, 2)
    ->setTimeouts(10, 30);
$response = $api->get('https://example.com/secure-endpoint');
```

---

## Error handling
When a call fails:
- `$api->responseBody` is `false`.
- `$api->error` contains a cURL error message (if any).
- `$api->http_response_code` contains the HTTP status code (0 if the request did not complete).

Always check both the HTTP status code and response body to determine success.

---

## Tips and caveats
- The default header behavior sets `Content-Type: application/json` for JSON flows.
- If you set `authorizationHeader` as a string, legacy behavior will overwrite headers with `Authorization: ...`. If you need combined headers, set `Authorization` via `$api->header['Authorization']` instead, or avoid setting `authorizationHeader` as a string.
- For multipart requests with files, let cURL set the Content-Type (boundary) automatically.
- JSON-RPC is just JSON; use `post()` with a JSON-serializable array.

---

## FAQ
- Does this change break existing code?
  - No. All enhancements are opt-in. Default behavior remains unchanged.

- Can I send form-urlencoded or multipart bodies?
  - Yes, via the proposed helper methods: `postForm()` and `postMultipart()`.

- How do retries work?
  - Retries apply only if you configure them with `setRetries()`. They can trigger on specific HTTP codes and/or network errors.

- How do I use client SSL certificates?
  - Call `setSsl(cert, key, ca, verifyPeer, verifyHost)` with your paths and verification settings.

---

## Contributing
If you plan to contribute these enhancements to GEMVC:
- Keep defaults unchanged to preserve backward compatibility.
- Provide unit/integration tests for new code paths (form, multipart, raw, timeouts, retries, SSL).
- Update this document with any newly accepted features.
