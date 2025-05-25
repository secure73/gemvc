# Security Guide

## Overview

GEMVC implements multiple layers of security to protect your application from common vulnerabilities and attacks. This guide covers security best practices and features available in the framework.

## Core Security Features

- Input validation and sanitization
- CSRF protection
- XSS prevention
- SQL injection protection
- Secure password handling
- Rate limiting
- Secure headers
- File upload security

## Input Security

### 1. Request Validation

```php
use Gemvc\Http\Request;

class UserController {
    public function create(Request $request): JsonResponse {
        // Define and validate input schema
        if (!$request->definePostSchema([
            'username' => 'string',
            'email' => 'string',
            'password' => 'string'
        ])) {
            return $request->returnResponse();
        }
        
        // Validate string lengths
        if (!$request->validateStringPosts([
            'username' => ['min' => 3, 'max' => 50],
            'password' => ['min' => 8]
        ])) {
            return $request->returnResponse();
        }
        
        // Your code here
    }
}
```

### 2. Type Checking

```php
use Gemvc\Helper\TypeChecker;

// Validate email
if (!TypeChecker::check('email', $email)) {
    throw new ValidationException('Invalid email format');
}

// Validate URL
if (!TypeChecker::check('url', $url)) {
    throw new ValidationException('Invalid URL format');
}

// Validate with custom options
if (!TypeChecker::check('string', $password, [
    'minLength' => 8,
    'maxLength' => 100,
    'regex' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
])) {
    throw new ValidationException('Password does not meet requirements');
}
```

## Database Security

### 1. Query Building

```php
use Gemvc\Database\QueryBuilder;

// Safe query building
$users = QueryBuilder::select('id', 'username', 'email')
    ->from('users')
    ->whereEqual('status', 'active')
    ->limit(10)
    ->run($pdoQuery);

// Parameter binding (automatic)
$user = QueryBuilder::select()
    ->from('users')
    ->whereEqual('email', $email)
    ->limit(1)
    ->run($pdoQuery);
```

### 2. Table Security

```php
use Gemvc\Database\TableGenerator;

// Create table with security constraints
$generator = new TableGenerator();
$generator
    ->addIndex('email', true)  // Unique index
    ->setNotNull('username')
    ->setDefault('is_active', true)
    ->addCheck('password', 'LENGTH(password) >= 8')
    ->createTableFromObject(new User());
```

## File Security

### 1. Secure File Upload

```php
use Gemvc\Helper\FileHelper;

// Secure file handling
$file = new FileHelper(
    $_FILES['upload']['tmp_name'],
    "storage/uploads/" . StringHelper::randomString(16)
);

// Move and encrypt file
if ($file->moveAndEncrypt()) {
    $encryptedPath = $file->outputFile;
}
```

### 2. Image Processing

```php
use Gemvc\Helper\ImageHelper;

// Secure image processing
$image = new ImageHelper($uploadedFile);
$image->convertToWebP(80);  // Convert to WebP with quality 80
```

## API Security

### 1. Rate Limiting

```php
use Gemvc\Http\Request;

class ApiController {
    private const MAX_REQUESTS = 100;
    private const WINDOW_SECONDS = 3600;
    
    public function handleRequest(Request $request): JsonResponse {
        // Check rate limit
        if (!$this->checkRateLimit($request->userId())) {
            return Response::tooManyRequests('Rate limit exceeded');
        }
        
        // Your code here
    }
}
```

### 2. CORS Configuration

```php
// Configure CORS headers
header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');  // 24 hours
```

## Best Practices

### 1. Password Security
- Use Argon2id for password hashing
- Implement password complexity requirements
- Add rate limiting for login attempts
- Use secure password reset mechanisms

### 2. Session Security
- Use secure session configuration
- Implement session timeout
- Use secure session storage
- Implement session fixation protection

### 3. Data Protection
- Encrypt sensitive data at rest
- Use TLS for data in transit
- Implement proper key management
- Regular security audits

### 4. Error Handling
- Don't expose sensitive information in errors
- Log security-related events
- Implement proper error responses
- Use custom error pages

## Security Headers

```php
// Essential security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
```

## Advanced Security Features

### 1. Request Validation Middleware

```php
class SecurityMiddleware {
    public function handle(Request $request): void {
        // Validate request origin
        $this->validateOrigin($request);
        
        // Check for common attack patterns
        $this->checkAttackPatterns($request);
        
        // Validate request size
        $this->validateRequestSize($request);
        
        // Add security headers
        $this->addSecurityHeaders();
    }
}
```

### 2. Audit Logging

```php
class SecurityLogger {
    public function logSecurityEvent(
        string $event,
        array $data,
        string $severity = 'info'
    ): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'data' => $data,
            'severity' => $severity,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];
        
        // Log to secure storage
        $this->storeLog($logEntry);
    }
}
```

## Next Steps

- [Authentication Guide](authentication.md)
- [Performance Guide](performance.md)
- [Deployment Guide](deployment.md) 