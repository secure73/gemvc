# Authentication Guide

## Overview

GEMVC provides a robust authentication system built on JWT (JSON Web Tokens) with support for role-based access control. The authentication is integrated into the Request class for seamless use throughout your application.

## Core Features

- JWT-based authentication
- Role-based access control
- Token refresh mechanism
- Secure password hashing
- Automatic error handling

## Configuration

### Environment Variables

```env
# Authentication Settings
TOKEN_SECRET='your_secret_key'
TOKEN_ISSUER='your_api_name'
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200  # 12 hours
ACCESS_TOKEN_VALIDATION_IN_SECONDS=15800   # ~4.4 hours
```

## Basic Usage

### 1. Simple Authentication

```php
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class UserController {
    public function getProfile(Request $request): JsonResponse {
        // Simple authentication check
        if (!$request->auth()) {
            return $request->returnResponse();
        }
        
        // Get user information
        $userId = $request->userId();
        $userRole = $request->userRole();
        
        // Your code here
    }
}
```

### 2. Role-Based Authorization

```php
public function adminDashboard(Request $request): JsonResponse {
    // Check for admin role
    if (!$request->auth(['admin'])) {
        return $request->returnResponse();
    }
    
    // Your admin code here
}

public function editorContent(Request $request): JsonResponse {
    // Check for either admin or editor role
    if (!$request->auth(['admin', 'editor'])) {
        return $request->returnResponse();
    }
    
    // Your editor code here
}
```

### 3. Login Implementation

```php
public function login(Request $request): JsonResponse {
    // Validate input
    if (!$request->definePostSchema([
        'email' => 'string',
        'password' => 'string'
    ])) {
        return $request->returnResponse();
    }
    
    // Get credentials
    $email = $request->post['email'];
    $password = $request->post['password'];
    
    // Verify credentials
    $user = $this->verifyCredentials($email, $password);
    if (!$user) {
        return Response::unauthorized('Invalid credentials');
    }
    
    // Generate tokens
    $accessToken = $this->generateAccessToken($user);
    $refreshToken = $this->generateRefreshToken($user);
    
    return Response::success([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'expires_in' => ACCESS_TOKEN_VALIDATION_IN_SECONDS
    ]);
}
```

### 4. Token Refresh

```php
public function refreshToken(Request $request): JsonResponse {
    if (!$request->definePostSchema([
        'refresh_token' => 'string'
    ])) {
        return $request->returnResponse();
    }
    
    $refreshToken = $request->post['refresh_token'];
    
    // Verify refresh token
    $user = $this->verifyRefreshToken($refreshToken);
    if (!$user) {
        return Response::unauthorized('Invalid refresh token');
    }
    
    // Generate new tokens
    $accessToken = $this->generateAccessToken($user);
    $refreshToken = $this->generateRefreshToken($user);
    
    return Response::success([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'expires_in' => ACCESS_TOKEN_VALIDATION_IN_SECONDS
    ]);
}
```

## Best Practices

### 1. Token Management
- Use short-lived access tokens (4-5 hours)
- Implement refresh token rotation
- Store refresh tokens securely
- Implement token revocation

### 2. Password Security
- Use strong password hashing (Argon2id recommended)
- Implement password complexity requirements
- Add rate limiting for login attempts
- Use secure password reset mechanisms

### 3. Error Handling
- Use consistent error responses
- Don't expose sensitive information
- Log authentication failures
- Implement proper session management

### 4. Security Headers
```php
// Add security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

## Advanced Features

### 1. Multi-Factor Authentication
```php
public function verifyMFA(Request $request): JsonResponse {
    if (!$request->definePostSchema([
        'mfa_code' => 'string'
    ])) {
        return $request->returnResponse();
    }
    
    $mfaCode = $request->post['mfa_code'];
    $userId = $request->userId();
    
    // Verify MFA code
    if (!$this->verifyMFACode($userId, $mfaCode)) {
        return Response::unauthorized('Invalid MFA code');
    }
    
    // Generate final tokens
    return $this->generateTokens($userId);
}
```

### 2. Session Management
```php
public function logout(Request $request): JsonResponse {
    $userId = $request->userId();
    
    // Invalidate refresh token
    $this->invalidateRefreshToken($userId);
    
    // Clear session data
    $this->clearUserSession($userId);
    
    return Response::success('Logged out successfully');
}
```

## Next Steps

- [Security Guide](security.md)
- [API Features](../features/api.md)
- [Performance Guide](performance.md) 