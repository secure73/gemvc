# 🚀 GEMVC

Transform your PHP development with GEMVC - where security meets simplicity! Build professional, secure APIs in minutes, not hours.

## 📋 Table of Contents
- [Overview](#overview)
- [Installation](#-5-second-installation)
- [Quick Start](#-quick-start)
- [Getting Started Guide](#-getting-started-guide)
- [Key Components](#-key-components)
- [Why GEMVC Stands Out](#-why-gemvc-stands-out)
  - [Security Features](#️-security-features)
  - [Error Handling](#-robust-error-handling)
  - [JWT Authentication](#-built-in-jwt-authentication)
  - [Dual Server Support](#-dual-server-support)
  - [Real-Time Communication](#-real-time-communication)
  - [Developer Experience](#-developer-experience)
  - [Automatic Table Generator](#-automatic-table-generator)
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

# Database Connection Pool
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=10
DB_CONNECTION_MAX_AGE=3600

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

## 📘 Getting Started Guide

Create a complete API endpoint with authentication, validation, and error handling:

```php
<?php
// api/UserController.php

use Gemvc\Http\ApacheRequest;
use Gemvc\Http\JsonResponse;
use Gemvc\Database\PdoConnection;
use Gemvc\Database\QueryBuilder;

class UserController {
    private PdoConnection $db;
    
    public function __construct() {
        $this->db = new PdoConnection();
    }
    
    public function getUsers(ApacheRequest $request) {
        // 1. Authenticate & authorize
        if (!$request->auth(['admin', 'system_manager'])) {
            return $request->returnResponse(); // Returns 401 or 403 with details
        }
        
        // 2. Validate query parameters
        if (!$request->defineGetSchema([
            '?limit' => 'int',
            '?page' => 'int',
            '?status' => 'string'
        ])) {
            return $request->returnResponse(); // Returns 400 with validation details
        }
        
        // 3. Type-safe parameter extraction
        $limit = $request->intValueGet('limit') ?: 10;
        $page = $request->intValueGet('page') ?: 1;
        $status = $request->get['status'] ?? 'active';
        
        // 4. Build and execute query
        $query = QueryBuilder::select('id', 'name', 'email', 'created_at')
            ->from('users')
            ->whereEqual('status', $status);
            
        // 5. Pagination
        $total = $query->count($this->db);
        $users = $query->limit($limit)
            ->offset(($page - 1) * $limit)
            ->run($this->db);
            
        // 6. Return structured response
        return (new JsonResponse())->success([
            'users' => $users,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
}
```

## 🧩 Key Components

| Component | Description | Key Features |
|-----------|-------------|-------------|
| **Database** | SQL query building & execution | Type-safe queries, injection protection, table generation |
| **HTTP** | Request/response handling | Validation, auth, WebSockets |
| **Security** | Security features | Encryption, sanitization, protection |
| **Helpers** | Utility classes | File, image, type handling |
| **WebSocket** | Real-time communication | Redis scaling, heartbeat, channels |

---

## 🌟 Why GEMVC Stands Out

### 🛡️ Security Features
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

**Use Case:** Securely handle sensitive file uploads with minimal code and maximum protection.

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

**Use Case:** Build APIs with consistent error responses that provide clear messages to clients.

### 🔑 Built-in JWT Authentication
```php
// Easy setup in .env file
// TOKEN_SECRET='your_jwt_secret_key'
// TOKEN_ISSUER='your_api_name'
// REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
// ACCESS_TOKEN_VALIDATION_IN_SECONDS=15800

// 1. Simple authentication - returns true/false
if (!$request->auth()) {
    // Already sets proper 401 response with details
    return $request->returnResponse();
}

// 2. Role-based authorization in one line
if (!$request->auth(['admin', 'system_manager'])) {
    // Already sets 403 response with specific role error
    //in this case only admin or system_manager user can perform this action!
    return $request->returnResponse();
}

// 3. Smart token extraction and verification
// - Automatically checks Authorization header
// - Validates expiration and signature
// - Sets detailed error messages on failure

// 4. Type-safe user information access with validation
$userId = $request->userId(); // Returns int or null with proper error response
$userRole = $request->userRole(); // Returns string or null with proper error response

// 5. Manual token management when needed
$token = $request->getJwtToken();
```

**Use Case:** Implement secure, role-based API authentication with minimal boilerplate code.

---

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

**Use Case:** Start with traditional Apache/Nginx setup, then easily scale to high-performance OpenSwoole when needed without code changes.

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

**Use Case:** Build real-time chat applications, notifications, or live dashboards with automatic scaling across servers.

### 🏗️ Automatic Table Generator
```php
// Define your model with PHP properties
class User {
    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public ?string $bio = null;
    public bool $is_active = true;
    public string $created_at;
    
    // Properties starting with _ are ignored
    private string $_tempData;
    
    public function getTable(): string {
        return 'users';
    }
}

// Create database table with just a few lines!
$generator = new TableGenerator();

// Fluent interface for configuration
$generator
    // Add indexes
    ->addIndex('username', true)  // Unique index
    ->addIndex('email', true)     // Unique index
    
    // Add constraints
    ->setNotNull('username')
    ->setDefault('is_active', true)
    ->setDefault('created_at', 'CURRENT_TIMESTAMP')
    ->addCheck('username', 'LENGTH(username) >= 3')
    
    // Create the table with all configurations
    ->createTableFromObject(new User());

// Add a composite unique constraint
$generator->makeColumnsUniqueTogether(
    'user_addresses', 
    ['user_id', 'address_type']
);

// Update table when model changes
$generator = new TableGenerator();
$generator->updateTable(new User(), null, true);  // true = remove columns no longer in object

// Safely remove a specific column
$generator->removeColumn('users', 'temporary_field');
```

**Use Case:** Automatically generate and maintain database tables from your PHP models with indexes, constraints, and validation, eliminating manual SQL schema creation and migration scripts.

---

### 👨‍💻 Developer Experience

#### ⚡ Lightning-Fast Development
```php
// Modern image processing in one line
$image = new ImageHelper($uploadedFile)->convertToWebP(80);

// Validation in one line
if (!$request->definePostSchema(['email' => 'email', 'name' => 'string', '?bio' => 'string'])) {
    return $request->returnResponse();
}

// Type-safe database queries
$users = QueryBuilder::select('id', 'name')
    ->from('users')
    ->whereLike('name', "%$searchTerm%")
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->run($pdoQuery);
    
// Object mapping with consistent naming
$user = $request->mapPostToObject(new User(), ['username', 'email', 'first_name', 'last_name']);

// Clean, structured API responses
return (new JsonResponse())->success($data)->show();
```

#### 🤖 AI-Ready Framework
- **Dual AI Support**: 
  - `AIAssist.jsonc`: Real-time AI coding assistance
  - `GEMVCLibraryAPIReference.json`: Comprehensive API documentation
- **Smart Code Completion**: AI tools understand our library structure
- **Intelligent Debugging**: Better error analysis and fixes
- **Future-Ready**: Ready for emerging AI capabilities

#### 🎈 Lightweight & Flexible
- **Minimal Dependencies**: Just 3 core packages
- **Zero Lock-in**: No rigid rules or forced patterns
- **Cherry-Pick Features**: Use only what you need
- **Framework Agnostic**: Works with any PHP project
- **Server Agnostic**: Same code works on Apache and OpenSwoole

**Use Case:** Rapidly prototype and build new features with minimal boilerplate code and maximum productivity.

---

## 💪 Core Features

### 🏗️ Modern Architecture
- **Type Safety**: PHP 8.0+ features
- **Modular Design**: Clear separation of concerns
- **Smart Patterns**: Factory, Builder, Traits
- **Clean Structure**: Intuitive organization
- **Consistent Naming**: camelCase conventions throughout

### 🔋 Efficient Resource Management
- **Lazy Database Connections**: Connections only established when actually needed
- **Model Efficiency**: Table and model classes can be instantiated without database overhead
- **Advanced Connection Pooling**: Sophisticated connection management with:
  - Parameter-based connection sharing
  - Automatic connection health verification
  - Time-based connection expiration
  - Configurable pool sizes
  - Efficient resource tracking
- **Automatic Cleanup**: Resources properly released through destructors
- **Memory Optimization**: Smart image processing

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

### 📊 Database & ORM Features
- **Query Builder**: Intuitive database operations with automatic parameter binding
- **Table Generator**: Create tables from PHP objects using reflection
- **Schema Management**: Add indexes, constraints, and relationships
- **Type Mapping**: Automatic conversion between PHP and SQL types
- **Transaction Support**: All operations wrapped in transactions
- **Column Constraints**: Support for NOT NULL, DEFAULT values, and CHECK constraints

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
| **Schema Management** | Manual SQL CREATE TABLE statements | Automatic table generation from PHP objects |
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

