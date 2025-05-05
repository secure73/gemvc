# 🚀 GEMVC

Transform your PHP development with GEMVC - where security meets simplicity! Build professional, secure APIs in minutes, not hours.

## 📋 Table of Contents
- [Overview](#overview)
- [Installation](#-5-second-installation)
- [Quick Start](#-quick-start)
- [Key Components](#-key-components)
- [Why GEMVC Stands Out](#-why-gemvc-stands-out)
- [Core Features](#-core-features)
- [Requirements](#-requirements)
- [Perfect For](#-perfect-for)
- [Documentation](#-documentation)
- [About](#about)

## Overview

```php
// From complex, error-prone code...
$stmt = $pdo->prepare("SELECT u.id, u.name FROM users WHERE status = ?");
$stmt->execute(['active']);

// To elegant, secure simplicity! 😍
$users = QueryBuilder::select('u.id', 'u.name')
    ->from('users')
    ->whereEqual('status', 'active')
    ->run($pdoQuery);
```

## 🔥 5-Second Installation
```bash
composer require gemvc/library
```

## 🚀 Quick Start

### 1. Configure Your Magic
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=your_db
DB_USER=root
DB_PASSWORD='yourPassword'

# Security Settings
TOKEN_SECRET='your_secret'
TOKEN_ISSUER='your_api'
```

### 2. Start Building
```php
// Create an API endpoint
class UserController {
    public function getUsers(ApacheRequest $request) {
        // Smart type-safe value extraction
        $limit = $request->intValueGet('limit') ?: 10;
        
        $users = QueryBuilder::select('id', 'name')
            ->from('users')
            ->whereEqual('status', 'active')
            ->limit($limit)
            ->run($this->db);
            
        return (new JsonResponse())->success($users);
    }
}
```

## 🧩 Key Components

| Component | Description | Key Features |
|-----------|-------------|-------------|
| **Database** | SQL query building & execution | Type-safe queries, injection protection |
| **HTTP** | Request/response handling | Validation, auth, WebSockets |
| **Security** | Security features | Encryption, sanitization, protection |
| **Helpers** | Utility classes | File, image, type handling |
| **WebSocket** | Real-time communication | Redis scaling, heartbeat, channels |

---

## 🌟 Why GEMVC Stands Out

### 🛡️ Bank-Grade Security, Zero Effort
```php
// Automatic protection against:
// ✓ SQL Injection
// ✓ XSS Attacks
// ✓ Path Traversal
// ✓ Shell Injection
// ✓ File Upload Vulnerabilities

// Military-grade file encryption in just 3 lines!
$file = new FileHelper($_FILES['upload']['tmp_name'], 'secure/file.dat');
$file->secret = $encryptionKey;
$file->moveAndEncrypt();  // AES-256-CBC + HMAC verification 🔐
```

### 🔄 Robust Error Handling
```php
// Consistent error responses with appropriate status codes
public function authorizeUser() {
    // Smart authentication with proper error responses
    $userId = $request->userId();
    
    // If invalid token or missing user ID, automatic 401/403 responses
    if (!$userId) {
        return $request->returnResponse(); // Already set with proper error info
    }
    
    // Type-safe value extraction with built-in validation
    $amount = $request->floatValueGet('amount');
    if (!$amount) {
        return $request->returnResponse(); // Returns 400 Bad Request with details
    }
    
    return $this->processTransaction($userId, $amount);
}
```

### 🔀 Dual Server Support
```php
// Same business logic, different server environments!

// --- APACHE/NGINX with PHP-FPM ---
function processUser($request) {
    if ($request->auth(['admin'])) {
        return (new JsonResponse())->success([
            'message' => 'Hello ' . $request->userId()
        ]);
    }
    return $request->returnResponse(); // Returns error response
}

// Apache handler
$request = new ApacheRequest(); // Handles traditional PHP request
$response = processUser($request->request);
$response->show(); // Output JSON

// --- OPENSWOOLE HIGH-PERFORMANCE SERVER ---
$server = new \Swoole\HTTP\Server('0.0.0.0', 8080);
$server->on('request', function($swooleRequest, $swooleResponse) {
    // Same code, different environment!
    $request = new SwooleRequest($swooleRequest);
    $response = processUser($request->request);
    $swooleResponse->end($response->toJson());
});
$server->start();
```

### 🔄 Real-Time Communication
```php
// Set up WebSocket server with advanced features
$server = new \Swoole\WebSocket\Server('0.0.0.0', 9501);

// Initialize handler with scalability options
$handler = new SwooleWebSocketHandler([
    'connectionTimeout' => 300,
    'maxMessagesPerMinute' => 60,
    'heartbeatInterval' => 30,
    'redis' => [
        'enabled' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'prefix' => 'websocket:'
    ]  // Scale across servers with automatic failover!
]);

// Register events and start server
$server->on('open', [$handler, 'onOpen']);
$server->on('message', [$handler, 'onMessage']);
$server->on('close', [$handler, 'onClose']);
$handler->registerHeartbeat($server);
$server->start();
```

### 🤖 AI-Ready Framework
- **Dual AI Support**: 
  - `AIAssist.jsonc`: Real-time AI coding assistance
  - `GEMVCLibraryAPIReference.json`: Comprehensive API documentation
- **Smart Code Completion**: AI tools understand our library structure
- **Intelligent Debugging**: Better error analysis and fixes
- **Future-Ready**: Ready for emerging AI capabilities

### 🎈 Lightweight & Flexible
- **Minimal Dependencies**: Just 3 core packages
- **Zero Lock-in**: No rigid rules or forced patterns
- **Cherry-Pick Features**: Use only what you need
- **Framework Agnostic**: Works with any PHP project
- **Server Agnostic**: Same code works on Apache and OpenSwoole

---

## 💪 Core Features

### 🏗️ Modern Architecture
- **Type Safety**: PHP 8.0+ features
- **Modular Design**: Clear separation of concerns
- **Smart Patterns**: Factory, Builder, Traits
- **Clean Structure**: Intuitive organization
- **Consistent Naming**: camelCase conventions throughout

### 🖥️ Server Flexibility
- **Apache/Nginx Support**: Traditional PHP request handling
- **OpenSwoole Support**: High-performance asynchronous server
- **Unified Request Interface**: Same code, different environments
- **Server-Specific Optimizations**: Get the best from each platform
- **Zero Code Changes**: Deploy to any environment without rewriting

### 🛡️ Security Features
- **Input Sanitization**: Automatic XSS prevention
- **Query Protection**: SQL injection prevention
- **File Security**: Path traversal protection
- **Email Safety**: Content security validation
- **WebSocket Protection**: Rate limiting and authentication
- **Robust Error Handling**: Consistent error responses with appropriate status codes

### 📡 Real-Time Communication
- **WebSocket Support**: Built-in OpenSwoole integration
- **Channel Messaging**: Pub/Sub pattern for group communication
- **Connection Management**: Automatic heartbeat and cleanup
- **Horizontal Scaling**: Redis integration for multi-server deployments with TTL-based memory management
- **Request Integration**: Same validation and authentication as REST APIs
- **Graceful Fallbacks**: Automatic local storage if Redis is unavailable

### 🎯 Developer Tools
- **Query Builder**: Intuitive database operations
- **File Processing**: Secure file handling with encryption
- **Image Handling**: WebP conversion and optimization
- **Type System**: Comprehensive validation
- **Value Extraction**: Type-safe methods for validated data access

### ⚡ Performance
- **Connection Pooling**: Smart database connections
- **Resource Management**: Efficient file streaming
- **Memory Optimization**: Smart image processing
- **Query Optimization**: Built-in performance features
- **WebSocket Efficiency**: Optimized for high-concurrency applications
- **Async Operations**: Non-blocking I/O with OpenSwoole

### 📊 Feature Comparison

| Feature | Traditional Approach | GEMVC Approach |
|---------|---------------------|----------------|
| **Database Queries** | Manual SQL strings, manual binding | Type-safe QueryBuilder, automatic binding |
| **Error Handling** | Inconsistent error responses | Standardized responses with proper status codes |
| **Authentication** | Manual token parsing, unclear errors | Built-in JWT handling with specific error responses |
| **WebSockets** | Manual implementation, no scaling | Ready-to-use handler with Redis scaling |
| **File Handling** | Manual validation, no encryption | Built-in validation, one-line encryption |
| **Server Support** | Either Apache OR Swoole | Same code on BOTH platforms |

---

## 📋 Requirements
- PHP 8.0+
- PDO Extension
- OpenSSL Extension
- GD Library
- OpenSwoole Extension (optional, for high-performance server and WebSockets)
- Redis Extension (optional, for WebSocket scaling)

## 🎯 Perfect For
- **Microservices**: Specific, efficient functionality
- **Legacy Projects**: Add modern features
- **New Projects**: Full control from day one
- **Learning**: Clear, understandable code
- **Real-Time Apps**: Chat, notifications, live updates
- **High-Load Applications**: Scale with OpenSwoole when needed

## 📚 Documentation
Want to dive deeper? Check out our [Documentation.md](Documentation.md)

## About
**Author:** Ali Khorsandfard <ali.khorsandfard@gmail.com>  
**GitHub:** [secure73/gemvc](https://github.com/secure73/gemvc)  
**License:** MIT

---
*Made with ❤️ for developers who love clean, secure, and efficient code.*

