# Authentication Features

## Overview

GEMVC provides a comprehensive authentication system with JWT support, role-based access control (RBAC), and secure session management, all integrated into the Request class.

## Core Features

### 1. JWT Authentication
- Token generation and validation
- Token refresh mechanism
- Claims management
- Token blacklisting

### 2. Role-Based Access Control
- Role validation
- Permission checking
- Role-based authorization
- Access control lists

### 3. Session Management
- Secure session handling
- Session persistence
- Session validation
- Session cleanup

## Configuration

### JWT Settings
```env
# JWT Configuration
JWT_SECRET=your-secret-key
JWT_ALGO=HS256
JWT_TTL=3600
JWT_REFRESH_TTL=604800
JWT_BLACKLIST_ENABLED=true
```

### Session Settings
```env
# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_SAME_SITE=strict
```

## Basic Usage

### Authentication
```php
use Gemvc\Http\Request;

// Simple authentication
if (!$request->auth()) {
    return $request->returnResponse();
}

// Role-based authorization
if (!$request->auth(['admin', 'editor'])) {
    return $request->returnResponse();
}

// Get user information
$userId = $request->userId();
$userRole = $request->userRole();
```

### JWT Token Management
```php
use Gemvc\Http\JWTToken;

$jwt = new JWTToken();

// Generate token
$token = $jwt->generate([
    'user_id' => 1,
    'role' => 'admin'
]);

// Validate token
$payload = $jwt->validate($token);

// Refresh token
$newToken = $jwt->refresh($token);

// Blacklist token
$jwt->blacklist($token);
```

### Session Management
```php
use Gemvc\Http\Session;

$session = new Session();

// Start session
$session->start();

// Set session data
$session->set('user_id', 1);

// Get session data
$userId = $session->get('user_id');

// Destroy session
$session->destroy();
```

## Advanced Features

### Token Claims
```php
$token = $jwt->generate([
    'user_id' => 1,
    'role' => 'admin',
    'permissions' => ['read', 'write'],
    'custom' => 'data'
], [
    'iss' => 'your-app',
    'aud' => 'your-users',
    'exp' => time() + 3600
]);
```

### Role-Based Authorization
```php
// Check multiple roles
if ($request->auth(['admin', 'editor', 'user'])) {
    // User has one of the required roles
}

// Get user role with error handling
$role = $request->userRole();
if ($role === null) {
    // Handle authentication error
    return $request->returnResponse();
}
```

### Session Security
```php
$session->setSecure(true);
$session->setHttpOnly(true);
$session->setSameSite('strict');

// Regenerate session
$session->regenerate();

// Set session lifetime
$session->setLifetime(3600);
```

## Best Practices

### 1. Token Security
- Use strong secrets
- Implement token rotation
- Handle token expiration
- Monitor token usage

### 2. Authorization
- Define clear roles
- Use role-based checks
- Cache permissions
- Audit access logs

### 3. Session Security
- Use secure cookies
- Implement session timeouts
- Handle session hijacking
- Clean up expired sessions

### 4. Error Handling
- Handle token validation
- Manage session errors
- Log security events
- Implement fallbacks

### 5. Performance
- Cache permissions
- Optimize token validation
- Use session pooling
- Monitor auth metrics

## Next Steps

- [Security Guide](../guides/security.md)
- [API Authentication](../guides/api-auth.md)
- [Session Management](../guides/sessions.md) 