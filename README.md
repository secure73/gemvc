# 🚀 GEMVC

Transform your PHP development with GEMVC - a modern PHP framework where security meets simplicity! Build professional, secure APIs in minutes, not hours.

## 📋 Table of Contents
- [Installation](#-5-second-installation)
- [Quick Start](#-quick-start)
- [Getting Started Guide](#-getting-started-guide)
- [Key Components](#-key-components)
- [Architecture Overview](#-architecture-overview)
- [Why GEMVC Stands Out](#-why-gemvc-stands-out)
  - [Security Features](#️-security-features)
  - [Error Handling](#-robust-error-handling)
  - [JWT Authentication](#-built-in-jwt-authentication)
  - [Dual Server Support](#-dual-server-support)
  - [Real-Time Communication](#-real-time-communication)
  - [Developer Experience](#-developer-experience)
  - [Automatic Table Generator](#-automatic-table-generator)
  - [Table Database Abstraction](#-table-database-abstraction)
- [Core Features](#-core-features)
- [Requirements](#-requirements)
- [Perfect For](#-perfect-for)
- [Documentation](#-documentation)
- [About](#about)

## 🔥 5-Second Installation

```bash
composer require gemvc/library
```

### Initialize Your Project

After installing the library, initialize your project with:

```bash
# For Apache/Nginx setup
php vendor/gemvc/library/src/bin/init.php apache

# For OpenSwoole setup
php vendor/gemvc/library/src/bin/init.php swoole
```

This script will:
- Create the necessary directory structure (`/app`, `/app/api`, `/app/controller`, `/app/model`, `/app/table`)
- Generate a sample `.env` file with default configuration
- Copy appropriate startup files to your project root based on platform choice
- Set up local command wrappers

> Note: If the above command doesn't work, check if the path exists. The script may be located at `vendor/gemvc/library/init.php` depending on your installation method.

### Generate Complete API Services

GEMVC includes a powerful CLI command for generating complete API services with a single command:

```bash
# Create a new service (e.g., User)
php vendor/bin/gemvc create:service User
```

This command automatically generates:

- **API Service** (`app/api/User.php`) - Handling requests with validation and routing
- **Controller** (`app/controller/UserController.php`) - Processing business logic
- **Model** (`app/model/UserModel.php`) - Managing data logic
- **Table** (`app/table/UserTable.php`) - Database abstraction layer

The generated code includes complete CRUD operations with proper validation, error handling, and documentation, following GEMVC's layered architecture pattern.

## 🚀 Quick Start

### 1. Configure Your Magic

Create an `.env` file in your app directory:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_db
DB_CHARSET=utf8mb4
DB_USER=root
DB_PASSWORD='yourPassword'
QUERY_LIMIT=10

# Database Connection Pool
DB_MIN_CONNECTION_POOL=1
DB_MAX_CONNECTION_POOL=10
DB_CONNECTION_TIME_OUT=20
DB_CONNECTION_EXPIER_TIME=20
DB_CONNECTION_MAX_AGE=3600

# Security Settings
TOKEN_SECRET='your_secret'
TOKEN_ISSUER='your_api'
LOGIN_TOKEN_VALIDATION_IN_SECONDS=789000
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
ACCESS_TOKEN_VALIDATION_IN_SECONDS=1200

# URL Configuration
SERVICE_IN_URL_SECTION=1
METHOD_IN_URL_SECTION=2

# OpenSwoole Configuration (optional)
IS_OPENSWOOLE=true
OPENSWOOLE_WORKERS=3
```

### 2. Start Building Your API

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
            ->run();
            
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
use Gemvc\Database\QueryBuilder;

class UserController {
    
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
        $total = $query->count();
        $users = $query->limit($limit)
            ->offset(($page - 1) * $limit)
            ->run();
            
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
| **Database** | SQL query building & execution | Type-safe queries, injection protection, table generation, ORM capabilities |
| **HTTP** | Request/response handling | Validation, auth, WebSockets |
| **Security** | Security features | Encryption, sanitization, protection |
| **Helpers** | Utility classes | File, image, type handling |
| **WebSocket** | Real-time communication | Redis scaling, heartbeat, channels |

## 🏗️ Architecture Overview

### Elegant Database Queries

```php
// From complex, error-prone code...
$stmt = $pdo->prepare("SELECT u.id, u.name FROM users WHERE status = ?");
$stmt->execute(['active']);

// To elegant, secure simplicity! 😍
$users = QueryBuilder::select('u.id', 'u.name')
    ->from('users')
    ->whereEqual('status', 'active')
    ->run();
```

### Database Components Hierarchy

```
PdoConnection  ←  Connection pooling, database management
    ↑
    │ aggregation
    │
QueryExecuter  ←  SQL query execution, lazy database loading
    ↑
    │ extends
    │
PdoQuery       ←  Query operations (select, insert, update, delete)
    ↑
    │ extends
    │
Table          ←  Database abstraction layer, fluent interface
```

In this architecture:

- **Table** extends **PdoQuery** to provide a fluent, object-oriented interface for working directly with database tables. It inherits all query capabilities while adding table-specific operations like insertSingleQuery, updateSingleQuery, and type mapping.

- **PdoQuery** extends **QueryExecuter** to add higher-level database operations like SELECT, INSERT, UPDATE, and DELETE queries while inheriting the core query execution capabilities.

- **QueryExecuter** aggregates **PdoConnection** to execute SQL queries while benefiting from connection pooling. It implements lazy loading, only establishing a database connection when actually needed for query execution.

- **PdoConnection** manages database connections with advanced pooling features, including connection reuse, health verification, automatic expiration, and efficient resource tracking.

This layered design allows each class to focus on a specific responsibility while building on the capabilities of its parent classes, resulting in a powerful and flexible database interaction system.

#### Enhanced QueryBuilder System

The QueryBuilder system has been improved with:

- **Non-static query building methods** for better encapsulation and state management
- **QueryBuilderInterface implementation** in query classes (Select, Insert, Update, Delete)
- **Error tracking mechanism** that allows QueryBuilder to store the last executed query's error
- **getError() method** for consistent error retrieval across all query operations

These improvements enhance error handling and provide more robust debugging capabilities. With the enhanced error handling, you can now easily identify and resolve database query issues:

```php
// Build and execute a query with error handling
$users = QueryBuilder::select('id', 'name')
    ->from('users')
    ->whereEqual('status', 'active')
    ->run();

// If the query fails, check for errors
if ($users === false) {
    $error = QueryBuilder::getError();
    echo "Query failed: " . $error;
}
```

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
    // In this case only admin or system_manager user can perform this action!
    return $request->returnResponse();
}

// 3. Smart token extraction and verification
// - Automatically checks Authorization header
// - Validates expiration and signature
// - Sets detailed error messages on failure

// 4. Type-safe user information access with validation
$userId = $request->userId(); // Returns int or null with proper error response
$userRole = $request->userRole(); // Returns string or null with proper error response
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
// In index.php (Swoole server startup)
$server = new \OpenSwoole\HTTP\Server('0.0.0.0', 9501);

// Set server configurations
$server->set([
    'worker_num' => 4,
    'max_request' => 1000,
    'enable_coroutine' => true,
    'document_root' => __DIR__,
    'enable_static_handler' => true
]);

// Handle each request
$server->on('request', function ($request, $response) {
    // Same code, different environment!
    $webserver = new SwooleRequest($request);
    $bootstrap = new SwooleBootstrap($webserver->request);
    $jsonResponse = $bootstrap->processRequest();
    
    $response->header('Content-Type', 'application/json');
    $response->end($jsonResponse->toJson());
});

$server->start();
```

**Use Case:** Start with traditional Apache/Nginx setup, then easily scale to high-performance OpenSwoole when needed without code changes.

#### OpenSwoole/Swoole Compatibility

GEMVC now provides seamless compatibility with both OpenSwoole and regular Swoole extensions:

- **Dynamic extension detection** - Automatically detects whether OpenSwoole or Swoole is installed
- **Runtime instance checking** - Uses proper class references without IDE warnings
- **Generic object type hints** - Maintains code clarity while ensuring runtime validation
- **Clear error messages** - Provides helpful feedback when neither extension is available

This compatibility layer ensures your application works smoothly regardless of which extension is installed on your server.

Implementation details:
```php
// Dynamic class selection based on available extensions
$swooleClass = class_exists('\\OpenSwoole\\WebSocket\\Server') 
    ? '\\OpenSwoole\\WebSocket\\Server' 
    : (class_exists('\\Swoole\\WebSocket\\Server') 
        ? '\\Swoole\\WebSocket\\Server' 
        : null);

if (!$swooleClass) {
    die("Error: Neither OpenSwoole nor Swoole extensions are installed.");
}

// Create server with the appropriate class
$server = new $swooleClass('0.0.0.0', 9501);

// Runtime instance checking for method calls
function handleSwooleObject($swooleObject) {
    // Works with both OpenSwoole and Swoole objects
    if ($swooleObject instanceof \OpenSwoole\WebSocket\Server ||
        $swooleObject instanceof \Swoole\WebSocket\Server) {
        // Safe to use common methods
        $swooleObject->push(...);
    }
}
```

### 🔄 Real-Time Communication
```php
// Set up WebSocket server with advanced features
$server = new \OpenSwoole\WebSocket\Server('0.0.0.0', 9501);

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

### 📚 Table Database Abstraction
```php
// Define a table class for database interaction
// Note: This is NOT a Model layer (which will be added later)
// This is a database abstraction layer for direct table operations
class UserTable extends Table {
    // Database table properties matching columns
    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public bool $is_active = true;
    public ?string $deleted_at = null;
    
    // Type mapping for database to PHP conversion
    protected array $_type_map = [
        'id' => 'int',
        'is_active' => 'bool',
        'deleted_at' => 'datetime'
    ];
    
    // Required constructor specifying the table name
    public function __construct() {
        parent::__construct('users');
    }
    
    // Custom database query methods
    public function findByEmail(string $email): ?self {
        return $this->select()
            ->where('email', $email)
            ->limit(1)
            ->run()[0] ?? null;
    }
}

// Database CRUD operations with fluent interface
// Create a new database record
$userTable = new UserTable();
$userTable->username = 'john_doe';
$userTable->email = 'john@example.com';
$userTable->password = password_hash('secure123', PASSWORD_DEFAULT);
$result = $userTable->insertSingleQuery();

// Read records with fluent query building
$activeUserRecords = (new UserTable())
    ->select()
    ->where('is_active', true)
    ->whereNull('deleted_at')
    ->whereBetween('created_at', date('Y-m-d', strtotime('-30 days')), date('Y-m-d'))
    ->orderBy('username', true) // true = ascending order
    ->run();

// Update a record
$userRecord = (new UserTable())->selectById(1);
if ($userRecord) {
    $userRecord->email = 'new.email@example.com';
    $userRecord->updateSingleQuery();
}

// Soft delete (sets deleted_at timestamp)
$userRecord->safeDeleteQuery();

// Restore soft-deleted record
$userRecord->restoreQuery();

// Hard delete
$userRecord->deleteSingleQuery();

// Pagination
$userTable = new UserTable();
$userTable->setPage($_GET['page'] ?? 1);
$userTable->limit(10);

$records = $userTable
    ->select()
    ->where('is_active', true)
    ->orderBy('created_at', false) // false = descending order
    ->run();

// Pagination metadata
$pagination = [
    'current_page' => $userTable->getCurrentPage(),
    'total_pages' => $userTable->getCount(),
    'total_records' => $userTable->getTotalCounts(),
    'per_page' => $userTable->getLimit()
];
```

**Use Case:** Work directly with database tables through a typed, fluent interface. The Table class provides database abstraction - a proper Model layer will be added in the future.

### 🏗️ Automatic Table Generator
```php
// Define a class that extends Table
class UserTable extends Table {
    // Properties that match your database columns
    public int $id;  // Will become INT(11) AUTO_INCREMENT PRIMARY KEY
    public string $username;  // Will become VARCHAR(255)
    public string $email;  // Will become VARCHAR(320)
    public string $password;
    public ?string $bio = null;
    public bool $is_active = true;
    public string $created_at;
    
    // Type mapping for automatic conversion
    protected array $_type_map = [
        'id' => 'int',
        'is_active' => 'bool',
        'created_at' => 'datetime'
    ];
    
    // Constructor passes table name to parent
    public function __construct() {
        parent::__construct('users');
    }
}

// Create database table from the Table-derived class
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
    
    // Create the table - table name is already in the UserTable class
    ->createTableFromObject(new UserTable());

// Add a composite unique constraint
$generator->makeColumnsUniqueTogether(
    'user_addresses', 
    ['user_id', 'address_type']
);

// Update table when model changes
$generator = new TableGenerator();
$generator->updateTable(new UserTable());  // Table name comes from the UserTable class

// Safely remove a specific column
$generator->removeColumn('users', 'temporary_field');
```

**Use Case:** Automatically generate and maintain database tables from your Table classes with indexes, constraints, and validation, eliminating manual SQL schema creation and migration scripts.

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
    ->run();
    
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
- **Platform-specific Components**: SwooleBootstrap and SwooleApiService for Swoole compatibility
- **CLI Platform Selection**: Easy switching between Apache and Swoole with `gemvc setup [apache|swoole]`

### 🛡️ Security Features
- **Input Sanitization**: Automatic XSS prevention
- **Query Protection**: SQL injection prevention
- **File Security**: Path traversal protection
- **Email Safety**: Content security validation
- **WebSocket Protection**: Rate limiting and authentication
- **Robust Error Handling**: Consistent error responses with appropriate status codes

### 📊 Database & ORM Features
- **Query Builder**: Intuitive database operations with automatic parameter binding
- **Table Class**: Database abstraction layer with typed properties and fluent query interface
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
- **CLI Tools**: Command-line utilities for project setup and service generation

### ⚡ Performance
- **Connection Pooling**: Smart database connections
- **Resource Management**: Efficient file streaming
- **Memory Optimization**: Smart image processing
- **Query Optimization**: Built-in performance features
- **WebSocket Efficiency**: Optimized for high-concurrency applications
- **Async Operations**: Non-blocking I/O with OpenSwoole
- **File Preloading**: Production mode file preloading for Swoole

### 📊 Feature Comparison

| Feature | Traditional Approach | GEMVC Approach |
|---------|---------------------|----------------|
| **Database Queries** | Manual SQL strings, manual binding | Type-safe QueryBuilder, automatic binding |
| **Table Interaction** | Manual object mapping to database | Typed Table class with automatic conversion |
| **Schema Management** | Manual SQL CREATE TABLE statements | Automatic table generation from PHP objects |
| **Error Handling** | Inconsistent error responses | Standardized responses with proper status codes |
| **Authentication** | Manual token parsing, unclear errors | Built-in JWT handling with specific error responses |
| **WebSockets** | Manual implementation, no scaling | Ready-to-use handler with Redis scaling |
| **File Handling** | Manual validation, no encryption | Built-in validation, one-line encryption |
| **Server Support** | Either Apache OR Swoole | Same code on BOTH platforms |
| **Platform Selection** | Manual configuration | Simple CLI command: `gemvc setup [apache|swoole]` |

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

