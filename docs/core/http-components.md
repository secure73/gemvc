# HTTP Components

## Overview

GEMVC provides a comprehensive set of HTTP components for handling requests, responses, and authentication.

## Core Components

### 1. Request (`src/HTTP/Request.php`)
- Request parsing
- Parameter handling
- Authentication
- Input validation

### 2. JsonResponse (`src/HTTP/JsonResponse.php`)
- JSON response formatting
- Status code handling
- Error formatting
- Success formatting

### 3. HtmlResponse (`src/HTTP/HtmlResponse.php`)
- HTML response formatting
- Template rendering
- Status code handling
- Error formatting

### 4. JWTToken (`src/HTTP/JWTToken.php`)
- JWT token generation
- Token validation
- Token refresh
- Role-based access

## Request Handling

### Parameter Access
```php
// GET parameters
$id = $request->getGet('id');

// POST parameters
$username = $request->getPost('username');

// PUT parameters
$data = $request->getPut();

// DELETE parameters
$id = $request->getDelete('id');

// All parameters
$all = $request->getAll();
```

### Parameter Validation
```php
// Validate required parameters
$this->validatePosts([
    'username' => 'string',
    'email' => 'email',
    'age' => 'int',
    '?bio' => 'string'  // Optional parameter
]);

// Validate GET parameters
$this->validateGets([
    'page' => 'int',
    'limit' => 'int'
]);
```

### File Upload
```php
// Handle file upload
$file = $request->getFile('avatar');
if ($file) {
    $file->moveTo('uploads/avatars');
}
```

## Response Handling

### JSON Response
```php
// Success response
return (new JsonResponse())->success([
    'data' => $result,
    'meta' => [
        'page' => $page,
        'limit' => $limit
    ]
]);

// Error response
return (new JsonResponse())->error('Error message', 400);

// Custom response
$response = new JsonResponse();
$response->setData(['custom' => 'data']);
$response->setStatus(201);
return $response;
```

### HTML Response
```php
// Render template
return (new HtmlResponse())->render('template.php', [
    'data' => $data
]);

// Error page
return (new HtmlResponse())->error('Error message', 404);
```

## Authentication

### JWT Token Generation
```php
// Generate token
$token = JWTToken::generate([
    'user_id' => 1,
    'role' => 'admin'
]);

// Generate refresh token
$refreshToken = JWTToken::generateRefreshToken($token);
```

### Token Validation
```php
// Validate token
if (!$request->auth()) {
    return $request->returnResponse();
}

// Validate role
if (!$request->auth(['admin'])) {
    return $request->returnResponse();
}

// Get token data
$tokenData = $request->getTokenData();
$userId = $tokenData['user_id'];
```

### Token Refresh
```php
// Refresh token
$newToken = JWTToken::refresh($refreshToken);

// Validate refresh token
if (!JWTToken::validateRefreshToken($refreshToken)) {
    return (new JsonResponse())->error('Invalid refresh token', 401);
}
```

## CORS Handling

### Configuration
```env
# CORS Settings
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization
CORS_MAX_AGE=3600
```

### Response Headers
```php
// Add CORS headers
$response->addHeader('Access-Control-Allow-Origin', '*');
$response->addHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
$response->addHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization');
```

## Error Handling

### HTTP Exceptions
```php
// Throw HTTP exception
throw new \Gemvc\HTTP\Exceptions\HttpException('Not Found', 404);

// Handle HTTP exception
try {
    // Your code
} catch (\Gemvc\HTTP\Exceptions\HttpException $e) {
    return (new JsonResponse())->error($e->getMessage(), $e->getCode());
}
```

### Validation Exceptions
```php
// Throw validation exception
throw new \Gemvc\HTTP\Exceptions\ValidationException('Invalid input');

// Handle validation exception
try {
    // Your code
} catch (\Gemvc\HTTP\Exceptions\ValidationException $e) {
    return (new JsonResponse())->error($e->getMessage(), 400);
}
```

## Best Practices

### 1. Request Handling
- Validate all input
- Use type-safe parameters
- Handle file uploads securely
- Sanitize user input

### 2. Response Handling
- Use appropriate status codes
- Format responses consistently
- Handle errors gracefully
- Add necessary headers

### 3. Authentication
- Use secure token generation
- Implement token refresh
- Validate roles properly
- Handle token expiration

### 4. CORS
- Configure CORS properly
- Limit allowed origins
- Set appropriate headers
- Handle preflight requests

### 5. Error Handling
- Use appropriate exceptions
- Handle errors consistently
- Provide meaningful messages
- Log errors properly

## Next Steps

- [Request Lifecycle](request-lifecycle.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 