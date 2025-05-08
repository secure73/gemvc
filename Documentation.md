# GEMVC Library Documentation

**Author:** Ali Khorsandfard <ali.khorsandfard@gmail.com>  
**GitHub Repository:** [secure73/gemvc](https://github.com/secure73/gemvc)

## Table of Contents
1. [Introduction](#introduction)
2. [AI Integration](#ai-integration)
3. [Architecture Overview](#architecture-overview)
4. [Installation & Configuration](#installation--configuration)
5. [Core Components](#core-components)
6. [Capabilities & Features](#capabilities--features)
7. [Security Features](#security-features)
8. [Quick Start Guide](#quick-start-guide)
9. [API Reference](#api-reference)
10. [Troubleshooting](#troubleshooting)

## Quick Links
- [AI Integration Guide](#ai-integration)
- [Installation Guide](#installation--configuration)
- [API Reference](#api-reference)
- [Troubleshooting Guide](#troubleshooting)

## AI Integration

This project includes an `AIAssist.jsonc` and `GEMVCLibraryAPIReference.json` files that provides comprehensive metadata about the framework's structure, components, and functionality. This file is designed to help AI assistants better understand and work with the codebase.

### AIAssist.jsonc Structure

The AIAssist.jsonc file contains detailed information about:
- Framework metadata (version, requirements, architecture)
- Core components and their relationships
- Security implementations and best practices
- Error patterns and handling strategies
- Configuration templates
- Integration patterns
- Performance optimization settings

AI assistants can use this file to:
- Understand component relationships and dependencies
- Access accurate method signatures and parameters
- Reference security patterns and implementations
- Find error handling strategies
- Locate configuration templates
- Understand version compatibility

### File Location
```
/
├── src/                 # Source code
├── AIAssist.jsonc       # AI assistance metadata
├── composer.json        # Dependencies
└── Documentation.md     # This documentation
```

## Introduction

GEMVC is a lightweight PHP library designed for building microservice-based RESTful APIs. It provides a comprehensive set of tools for database operations, HTTP handling, email management, and various utility functions.

### Key Features
- Fluent database query building with PDO
- Secure HTTP request/response handling
- Advanced email capabilities with SMTP support
- File and image processing utilities
- Built-in security features
- Type checking and validation

## Architecture Overview

### Directory Structure
```
src/
├── database/         # Database operations
│   ├── PdoConnection.php
│   ├── PdoQuery.php
│   ├── QueryBuilder.php
│   └── query/
│       ├── Delete.php
│       ├── Insert.php
│       ├── Select.php
│       └── Update.php
├── http/            # HTTP handling
│   ├── ApacheRequest.php
│   ├── ApiCall.php
│   └── JsonResponse.php
├── email/           # Email functionality
│   └── GemSMTP.php
└── helper/          # Utility classes
    ├── CryptHelper.php
    ├── FileHelper.php
    ├── ImageHelper.php
    ├── JsonHelper.php
    ├── StringHelper.php
    ├── TypeChecker.php
    └── TypeHelper.php
```

### Design Patterns
1. **Factory Pattern**
   - Used in QueryBuilder for creating query objects
   - Provides consistent interface for query creation

2. **Builder Pattern**
   - Implemented in query classes
   - Enables fluent interface for query construction

3. **Trait-based Composition**
   - WhereTrait for query conditions
   - LimitTrait for pagination

4. **Singleton Pattern**
   - Applied to database connections
   - Ensures resource efficiency

## Installation & Configuration

### Requirements
- PHP 8.0+
- PDO Extension
- OpenSSL Extension
- GD Library (for image processing)

### Installation Steps
1. Install via Composer:
```bash
composer require gemvc/library
```

2. Create `.env` file with the following settings:
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=db_name
DB_CHARSET=utf8
DB_USER=root
DB_PASSWORD='databasePassword'
QUERY_LIMIT=10

# Database Connection Pool Configuration
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=10
DB_CONNECTION_MAX_AGE=3600

# Authentication
TOKEN_SECRET='secret for your token'
TOKEN_ISSUER='your_api_name'
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
ACCESS_TOKEN_VALIDATION_IN_SECONDS=15800

# URL Configuration
SERVICE_IN_URL_SECTION=2
METHOD_IN_URL_SECTION=3

# OpenSwoole Configuration
SWOOLE_MODE=false
```

## Core Components

### 1. Database Layer

#### PdoConnection
Handles database connectivity and state management with advanced connection pooling.
```php
class PdoConnection {
    public function connect(): \PDO|null
    public function isConnected(): bool
    public function getError(): null|string
    public function db(): \PDO|null
    
    // Connection pool management
    public static function getMinPoolSize(): int
    public static function getMaxPoolSize(): int
    public static function getMaxConnectionAge(): int
    public static function getPoolSize(?string $key = null): int
    public static function getTotalConnections(): int
    public static function cleanExpiredConnections(?string $key = null): int
    public static function clearPool(?string $key = null): void
    
    // Connection lifecycle methods
    public function releaseConnection(): bool
}
```

##### Connection Pooling Features
- **Parameter-Based Pooling**: Connections grouped by database parameters
- **Connection Aging**: Automatic expiration of connections based on age
- **Pool Size Control**: Configurable minimum and maximum pool sizes
- **Health Verification**: Connections tested for validity before use and reuse
- **Resource Tracking**: Accurate monitoring of total active connections

##### Environment Configuration
```env
# Database Connection Pool Configuration
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=10
DB_CONNECTION_MAX_AGE=3600
```

These environment variables control:
- `MIN_DB_CONNECTION_POOL`: Minimum connections to maintain per pool
- `MAX_DB_CONNECTION_POOL`: Maximum connections allowed per pool
- `DB_CONNECTION_MAX_AGE`: Maximum age in seconds before a connection expires (default: 3600)

##### Connection Lifecycle
1. When `connect()` is called, expired connections are cleaned from the pool
2. The system tries to reuse an existing connection from the appropriate pool
3. If no valid connection exists in the pool, a new one is created
4. When `releaseConnection()` is called, the connection is validated and returned to the pool
5. Connections exceeding the maximum age are automatically removed

This system is particularly beneficial in high-concurrency environments and when using OpenSwoole, as it:
- Reduces connection overhead
- Prevents connection leaks
- Ensures database resource efficiency
- Automatically handles stale connections

#### Table
The Table class provides a comprehensive Object-Relational Mapping (ORM) layer with fluent query building capabilities. It extends the database abstraction through PdoQuery and offers a robust foundation for defining table-specific model classes.

```php
class Table extends PdoQuery {
    /**
     * Initialize a new Table instance
     * @param string $tableName Database table name (required)
     * @throws \InvalidArgumentException if table name is empty
     */
    public function __construct(string $tableName)
    
    /**
     * Inserts a single row into the database table
     * @return null|static The current instance with inserted id or null on failure
     */
    public function insertSingleQuery(): null|static
    
    /**
     * Updates a record based on its ID property
     * @return null|static Current instance or null on failure
     */
    public function updateSingleQuery(): null|static
    
    /**
     * Deletes a record by ID
     * @param int $id Record ID to delete
     * @return null|int ID of deleted record or null on failure
     */
    public function deleteByIdQuery(int $id): null|int
    
    /**
     * Marks a record as deleted (soft delete)
     * @return null|static Current instance or null on failure
     */
    public function safeDeleteQuery(): null|static
    
    /**
     * Restores a soft-deleted record
     * @return null|static Current instance or null on failure
     */
    public function restoreQuery(): null|static
    
    /**
     * Starts building a SELECT query
     * @param string|null $columns Columns to select (defaults to *)
     * @return self For method chaining
     */
    public function select(string $columns = null): self
    
    /**
     * Adds a WHERE condition
     * @param string $column Column name
     * @param mixed $value Value to match
     * @return self For method chaining
     */
    public function where(string $column, mixed $value): self
    
    /**
     * Adds a WHERE condition using OR operator
     * @param string $column Column name
     * @param mixed $value Value to match
     * @return self For method chaining
     */
    public function whereOr(string $column, mixed $value): self
    
    /**
     * Executes a SELECT query and returns results
     * @return array<static>|null Array of model instances or null on failure
     */
    public function run(): null|array
    
    /**
     * Selects a single row by ID
     * @param int $id Record ID to select
     * @return null|static Found instance or null if not found
     */
    public function selectById(int $id): null|static
    
    /**
     * Disables count subqueries for performance optimization
     * @return self For method chaining
     */
    public function skipCount(): self
}
```

##### Key Features

1. **ORM Capabilities**
   - Maps database rows to PHP objects and vice versa
   - Automated property hydration from query results
   - Type casting through configurable type mapping system

2. **Fluent Query Building**
   - Method chaining for readable query construction
   - Comprehensive WHERE clause options
   - JOIN support with multiple join types

3. **Built-in Pagination**
   - Automatic result counting and page calculation
   - Page navigation methods
   - Configurable page sizes

4. **Soft Delete Pattern**
   - Support for both hard and soft deletion
   - Restore capability for soft-deleted records
   - Integration with `is_active` status flags

5. **Type Safety**
   - PHP 8 type declarations for parameters and return values
   - Type mapping system for database to PHP type conversion
   - Support for date/time, boolean, and numeric type conversion

6. **Performance Optimizations**
   - Connection pooling through inheritance from PdoQuery
   - Optional count query skipping for better performance
   - Efficient query building with parameter binding

##### Type Mapping System
The Table class includes a protected `$_type_map` array property that enables automatic type conversion between database values and PHP types:

```php
protected array $_type_map = [
    'id' => 'int',
    'is_active' => 'bool',
    'created_at' => 'datetime'
];
```

Supported type conversions:
- `'int'` → Converts to PHP integer
- `'float'` → Converts to PHP float  
- `'bool'` → Converts to PHP boolean
- `'datetime'` → Creates a PHP DateTime object

##### Usage Pattern

Creating a table-specific model class:

```php
class UserTable extends Table
{
    public int $id;
    public string $username;
    public string $email;
    public ?string $deleted_at = null;
    public bool $is_active = true;
    
    protected array $_type_map = [
        'id' => 'int',
        'is_active' => 'bool',
        'deleted_at' => 'datetime'
    ];
    
    public function __construct()
    {
        parent::__construct('users');
    }
    
    // Custom methods for this specific table
    public function findByUsername(string $username): ?self
    {
        return $this->select()
            ->where('username', $username)
            ->limit(1)
            ->run()[0] ?? null;
    }
}
```

Basic CRUD operations:

```php
// Create a new user
$user = new UserTable();
$user->username = 'johndoe';
$user->email = 'john@example.com';
$user->is_active = true;
$user->insertSingleQuery();

// Retrieve a user by ID
$user = (new UserTable())->selectById(1);

// Update a user
$user->email = 'newemail@example.com';
$user->updateSingleQuery();

// Delete a user (soft delete)
$user->safeDeleteQuery();

// Restore a soft-deleted user
$user->restoreQuery();

// Hard delete a user
$user->deleteSingleQuery();
```

Complex queries:

```php
// Find active users created in the last week
$activeUsers = (new UserTable())
    ->select()
    ->where('is_active', true)
    ->whereBetween('created_at', date('Y-m-d', strtotime('-7 days')), date('Y-m-d'))
    ->orderBy('created_at', false) // Descending order
    ->limit(20)
    ->run();

// Find users with admin-like permissions
$adminUsers = (new UserTable())
    ->select()
    ->where('role', 'admin')
    ->whereOr('role', 'superuser')
    ->whereOr('has_admin_access', true)
    ->run();
```

Pagination example:

```php
$userTable = new UserTable();
$userTable->setPage($_GET['page'] ?? 1);
$userTable->limit(10);

$users = $userTable
    ->select()
    ->where('is_active', true)
    ->orderBy('username')
    ->run();

// Pagination metadata
$totalUsers = $userTable->getTotalCounts();
$totalPages = $userTable->getCount();
$currentPage = $userTable->getCurrentPage();
```

#### QueryBuilder
Provides fluent interface for building SQL queries.
```php
class QueryBuilder {
    public static function select(string ...$select): Select
    public static function insert(string $intoTableName): Insert
    public static function update(string $tableName): Update
    public static function delete(string $tableName): Delete
}
```

#### QueryExecuter
Executes SQL queries with lazy database connection loading.
```php
class QueryExecuter {
    /**
     * Constructor initializes execution timer but doesn't establish a database connection
     * Connection is only established when needed for query execution
     */
    public function __construct()
    
    /**
     * Destructor ensures resources are properly cleaned up
     */
    public function __destruct()
    
    /**
     * Lazy-loads the database connection when needed
     */
    private function ensureConnection(): bool
    
    /**
     * Prepare an SQL query for execution
     * Establishes a database connection if needed
     */
    public function query(string $query): void
    
    /**
     * Execute the prepared statement
     * Establishes a database connection if needed
     */
    public function execute(): bool
    
    /**
     * Release database resources and close connection
     */
    public function secure(): void
    
    // Result fetching methods
    public function fetchAll(): array|false
    public function fetchAllObjects(): array|false
    public function fetchAllClass(string $targetClassName): array|false
    public function fetchColumn(): mixed
    
    // Metadata methods
    public function affectedRows(): int|null
    public function lastInsertId(): string|false
    public function getExecutionTime(): float
    public function getError(): ?string
}
```

Key features:
- **Lazy Database Connection**: Connections are only established when actually needed for query operations
- **Resource Efficiency**: Child classes can be instantiated without creating unnecessary database connections
- **Automatic Resource Cleanup**: Connections are properly closed when the object is destroyed
- **Error Handling**: Comprehensive error reporting and tracking
- **Type Detection**: Automatic parameter type detection for binding
- **Result Fetching**: Multiple formats for retrieving query results
- **Performance Tracking**: Query execution time measurement

#### TableGenerator
Automatically creates and modifies database tables from PHP objects using reflection.
```php
class TableGenerator extends QueryExecuter {
    /**
     * Creates a table from an object
     * @param object $object The object to create a table from
     * @param string|null $tableName Table name or null to use object's getTable() method
     * @return bool Success status
     */
    public function createTableFromObject(object $object, string $tableName = null): bool
    
    /**
     * Makes a column in an existing table unique
     * @param string $tableName The table name
     * @param string $columnName The column to make unique
     * @param bool $dropExistingIndex Whether to drop existing indexes
     * @return bool Success status
     */
    public function makeColumnUnique(string $tableName, string $columnName, bool $dropExistingIndex = true): bool
    
    /**
     * Creates a composite unique constraint
     * @param string $tableName The table name
     * @param array $columnNames Array of column names to make unique together
     * @param string|null $indexName Optional custom index name
     * @param bool $dropExistingIndexes Whether to drop existing indexes
     * @return bool Success status
     */
    public function makeColumnsUniqueTogether(string $tableName, array $columnNames, ?string $indexName = null, bool $dropExistingIndexes = true): bool
    
    /**
     * Marks a property for indexing
     * @param string $propertyName The property to index
     * @param bool $unique Whether index should be unique
     * @return self For method chaining
     */
    public function addIndex(string $propertyName, bool $unique = false): self
    
    /**
     * Removes indexing from a property
     * @param string $propertyName The property to remove indexing from
     * @return self For method chaining
     */
    public function removeIndex(string $propertyName): self
    
    /**
     * Sets custom SQL properties for a column
     * @param string $propertyName The property name
     * @param string $columnProperties The SQL properties to add
     * @return self For method chaining
     */
    public function setColumnProperties(string $propertyName, string $columnProperties): self
    
    /**
     * Makes a column NOT NULL
     * @param string $propertyName The property name
     * @return self For method chaining
     */
    public function setNotNull(string $propertyName): self
    
    /**
     * Sets a default value for a column
     * @param string $propertyName The property name
     * @param mixed $defaultValue The default value
     * @return self For method chaining
     */
    public function setDefault(string $propertyName, mixed $defaultValue): self
    
    /**
     * Adds a CHECK constraint to a column
     * @param string $propertyName The property name
     * @param string $checkExpression The SQL expression for the check
     * @return self For method chaining
     */
    public function addCheck(string $propertyName, string $checkExpression): self
    
    /**
     * Removes a column from an existing table
     * @param string $tableName The table name
     * @param string $columnName The column to remove
     * @return bool Success status
     */
    public function removeColumn(string $tableName, string $columnName): bool
    
    /**
     * Updates an existing table based on changes in object properties
     * @param object $object The object with updated properties
     * @param string|null $tableName Table name or null to use object's getTable() method
     * @param bool $removeExtraColumns Whether to remove columns that don't exist in the object
     * @return bool Success status
     */
    public function updateTable(object $object, string $tableName = null, bool $removeExtraColumns = false): bool
}
```

Type mapping:
- PHP `int`/`integer` → SQL `INT(11)`
- PHP `float`/`double` → SQL `DOUBLE`
- PHP `bool`/`boolean` → SQL `TINYINT(1)`
- PHP `string` → SQL `VARCHAR(255)`
- PHP `datetime` → SQL `DATETIME`
- PHP `array` → SQL `JSON`
- Unknown types → SQL `TEXT`

Special field handling:
- Property named `id` → Auto-increment primary key
- Properties ending with `_id` → `INT(11)` (for foreign keys)
- Properties ending with `email` → `VARCHAR(320)` (max email length)

Properties that are skipped:
- Static properties
- Properties that start with underscore (_)
- Constants (read-only public properties in PHP 8.1+)

Key features:
- **PHP to SQL Type Mapping**: Automatically maps PHP types to appropriate SQL column types
- **Property Filtering**: Skips properties that shouldn't be database columns (static, underscore-prefixed, constants)
- **Special Field Handling**: Automatic handling for ID fields, foreign keys, and email addresses
- **Index Management**: Support for regular and unique indexes, including composite indexes
- **Column Constraints**: Support for NOT NULL, DEFAULT values, and CHECK constraints
- **Transaction Support**: All operations wrapped in transactions for atomicity
- **Schema Management**: Ability to update existing tables by adding new columns, modifying changed columns, and removing deleted columns

### 2. HTTP Layer

#### Request
A comprehensive HTTP request handler with validation, authentication, and error handling capabilities.

```php
class Request {
    // Key Properties
    public null|string|array $authorizationHeader;  // Authorization header value
    public array $post;                             // POST data
    public null|array $put;                         // PUT data
    public null|array $patch;                       // PATCH data 
    public string|array $get;                       // GET parameters
    public null|array $files;                       // Uploaded files
    public string $requestedUrl;                    // Current URL
    public ?string $requestMethod;                  // HTTP method (GET, POST, etc.)
    public bool $isAuthenticated;                   // Authentication status
    public bool $isAuthorized;                      // Authorization status
    public ?JsonResponse $response;                 // Response object for error states
    public ?string $error;                          // Error message if present
    
    // Authentication & Authorization
    public function auth(array $authRules=null): bool                   // Authenticate and authorize request
    public function userRole(): null|string                             // Get authenticated user's role
    public function userId(): null|int                                  // Get authenticated user's ID
    
    // Request Data Validation
    public function definePostSchema(array $toValidatePost): bool       // Validate POST data
    public function defineGetSchema(array $toValidateGet): bool         // Validate GET data
    public function definePutSchema(array $toValidatePut): bool         // Validate PUT data 
    public function definePatchSchema(array $toValidatePatch): bool     // Validate PATCH data
    
    // Data Mapping Methods
    public function mapPostToObject(object $object, array $manualMap = null): null|object // Map POST data to object
    public function mapPutToObject(object $object, array $manualMap = null): null|object  // Map PUT data to object
    
    // API Request Forwarding
    public function forwardPost(string $remoteApiUrl, string $authorizationHeader = null): JsonResponse // Forward POST request
    
    // Query Parameter Helpers
    public function filterable(array $searchableGetValues): bool        // Handle filterable parameters
    public function findable(array $filterableGetValues): bool          // Handle findable parameters
    public function sortable(array $sortableGetValues): bool            // Handle sortable parameters
    public function setPageNumber(): bool                               // Set page number for pagination
    public function setPerPage(): bool                                  // Set items per page
    
    // Value Extraction Helpers
    public function intValuePost(string $key): int|false                // Get validated integer from POST
    public function floatValuePost(string $key): float|false            // Get validated float from POST
    public function intValueGet(string $key): int|false                 // Get validated integer from GET
    public function floatValueGet(string $key): float|false             // Get validated float from GET
    
    // String Validation
    public function validateStringPosts(array $stringPosts): bool       // Validate string lengths
    
    // Error Handling
    private function setErrorResponse(array $errors, int $responseCode = 400): false // Centralized error handling
}
```

##### Authentication and Authorization
The Request class provides built-in JWT-based authentication with improved error handling:

```php
// Simple authentication
if (!$request->auth()) {
    return $response->unauthorized();
}

// Role-based authorization
if (!$request->auth(['admin', 'editor'])) {
    return $response->forbidden();
}

// Getting user info with robust error handling
$userId = $request->userId();  // Will return null and set appropriate error responses if JWT token is missing, invalid, or user_id is not set
$userRole = $request->userRole(); // Will return null and set appropriate error responses with proper HTTP status codes
```

##### Error Handling
The Request class now provides centralized error handling through the `setErrorResponse` method:

```php
// Internal use in methods
private function setErrorResponse(array $errors, int $responseCode = 400): false
{
    $this->error = implode(', ', $errors);
    
    // Use the response code parameter
    $this->response = match($responseCode) {
        401 => Response::unauthorized($this->error),
        403 => Response::forbidden($this->error),
        422 => Response::unprocessableEntity($this->error),
        default => Response::badRequest($this->error),
    };
    
    return false;
}

// This provides consistent error responses throughout the class
// with appropriate HTTP status codes (400, 401, 403, 422)
```

##### Mapping Request Data to Objects
The Request class can automatically map request data to object properties with improved naming conventions:

```php
// Map all POST data to user object
$user = $request->mapPostToObject(new User());
if (!$user) {
    return $request->returnResponse(); // Error during mapping
}

// Selective mapping with manual field selection
// Note the improved naming consistency (manualArray instead of manual_array)
$user = $request->mapPostToObject(new User(), ['username', 'email']);
```

#### SwooleWebSocketHandler
A WebSocket handler for OpenSwoole with advanced features for real-time applications.

```php
class SwooleWebSocketHandler {
    // Main Properties
    private array $connections;                 // Active connection tracking
    private array $channels;                    // Channel subscriptions
    private array $messageCounters;             // Rate limiting counters
    
    // Configuration Properties
    private int $connectionTimeout;             // Default: 300 seconds
    private int $maxMessagesPerMinute;          // Default: 60 messages/minute
    private int $heartbeatInterval;             // Default: 30 seconds
    private bool $useRedis;                     // Whether to use Redis for scaling
    private $redis;                             // Redis connection (optional)
    private string $redisPrefix;                // Redis key prefix
    
    // Constructor and initialization
    public function __construct(array $config = [])  // Initialize with optional configuration
    public function registerHeartbeat(\Swoole\WebSocket\Server $server): void  // Setup heartbeat timers
    
    // WebSocket Event Handlers
    public function onOpen($server, $request)   // Handle new connections
    public function onMessage($server, $frame)  // Process incoming messages
    public function onClose($server, $fd)       // Handle disconnections
    
    // Channel Operations
    private function handleSubscribe($server, $fd, Request $request)    // Process channel subscriptions
    private function handleUnsubscribe($server, $fd, Request $request)  // Process channel unsubscribes
    private function handleMessage($server, $fd, Request $request)      // Process and broadcast messages
    
    // Connection Management
    private function performHeartbeat(\Swoole\WebSocket\Server $server): void        // Ping all connections
    private function cleanupExpiredConnections(\Swoole\WebSocket\Server $server): void  // Remove inactive connections
    private function checkRateLimit(int $fd): bool                      // Enforce message rate limits
    private function updateLastActivity(int $fd): void                  // Track connection activity
    
    // Redis Integration Methods
    private function initRedis(array $config): void                     // Setup Redis connection
    private function storeConnectionInRedis(int $fd, array $data): void // Store connection data
    private function getConnectionFromRedis(int $fd): ?array            // Retrieve connection data
    private function getAllConnectionsFromRedis(): array                // Get all connections
    private function removeConnectionFromRedis(int $fd): void           // Remove connection
    private function subscribeChannelRedis(int $fd, string $channel): void  // Subscribe to channel
    private function unsubscribeChannelRedis(int $fd, string $channel): void  // Unsubscribe from channel
    private function checkRateLimitRedis(int $fd, int $now): bool       // Rate limiting with Redis
}
```

##### Key Features

1. **Connection Management**
   - Automatic tracking and cleanup of inactive connections
   - Configurable connection timeout (default: 300 seconds)
   - Event-based connection lifecycle handling
   - Detailed connection metadata storage

2. **Rate Limiting**
   - Per-client message rate limiting
   - Configurable message limits (default: 60 messages per minute)
   - Automatic throttling with informative client feedback
   - Sliding window implementation for fair limiting

3. **Heartbeat Mechanism**
   - Automatic ping/pong protocol implementation
   - Configurable heartbeat interval (default: 30 seconds)
   - Detection and cleanup of broken connections
   - Explicit timeout notifications to clients

4. **Channel-based Messaging**
   - Publisher/subscriber messaging patterns
   - Multi-channel subscription support
   - Target-specific message delivery
   - Per-connection channel tracking

5. **Redis Integration for Scalability**
   - Optional Redis support for multi-server deployments
   - Distributed connection and channel tracking
   - Scalable rate limiting across server instances
   - Automatic fallback to local storage if Redis is unavailable
   - Connection persistence with TTL to prevent memory leaks

##### Configuration Options

```php
$config = [
    // Basic settings
    'connectionTimeout' => 300,       // Seconds before inactive connections are closed
    'maxMessagesPerMinute' => 60,     // Rate limiting threshold
    'heartbeatInterval' => 30,        // Seconds between ping messages
    
    // Redis configuration (optional)
    'redis' => [
        'enabled' => true,            // Enable Redis integration
        'host' => '127.0.0.1',        // Redis server hostname
        'port' => 6379,               // Redis server port
        'password' => 'secret',       // Redis password (if required)
        'database' => 0,              // Redis database number
        'prefix' => 'websocket:'      // Key prefix for Redis entries
    ]
];
```

##### Message Protocol

The WebSocket handler uses a simple JSON-based message protocol:

**Client to Server Messages:**
```json
{
    "action": "subscribe|unsubscribe|message|pong",
    "data": {
        // Action-specific data
    }
}
```

**Server to Client Messages:**
```json
{
    "action": "welcome|ping|message|timeout",
    // Action-specific data
}
```

Common message types:
- `subscribe`: Join a channel
- `unsubscribe`: Leave a channel
- `message`: Send a message to a channel
- `ping`/`pong`: Heartbeat mechanism
- `welcome`: Initial connection data
- `timeout`: Connection timeout notification

##### Integration with Request Class

The SwooleWebSocketHandler uses the Request class for validation and authentication:

```php
// Inside handleSubscribe method
if ($request->definePostSchema([
    'channel' => 'string',
    '?options' => 'array'
])) {
    // Valid subscription request
    // ...
}
```

This integration allows you to leverage the same validation, authentication, and error handling patterns used throughout your REST API in your WebSocket application.

### 3. Email System

#### GemSMTP
Email handling with attachment and embedded image support.
```php
class GemSMTP {
    public function createMail(
        string $receiverEmail,
        string $receiverName,
        string $subject,
        string $htmlContent,
        string $contentLanguage = null
    ): bool

    public function addAttachment(string $filePath, ?string $showName = null): bool
    public function addEmbeddedImage(string $imagePath, string $cid): bool
    public function send(): bool
}
```

### GemSMTP Configuration
- Maximum File Size: 10MB (const MAX_FILE_SIZE)
- Maximum Content Size: 25MB (const MAX_CONTENT_SIZE)
- Retry Mechanism: 3 attempts (const MAX_RETRIES)
- Supported Languages: 45 languages including ar, az, ba, bg, bs, ca, cs, da, de, el, en...
- SSL Verification Options:
  - verify_peer
  - verify_peer_name
  - allow_self_signed
  - min_tls_version (TLSv1.2)

### 4. Helper Utilities

The GEMVC library provides a rich set of helper classes for common tasks. Here's a detailed overview of each helper class:

#### FileHelper
Secure file operations with encryption support.
```php
class FileHelper {
    public string $sourceFile;
    public string $outputFile;
    public ?string $error = null;
    public ?string $secret;          // Encryption key
    
    public function __construct(string $sourceFile, string $outputFile = null)
    
    // Secure file operations
    public function copy(): bool     // Uses escapeshellarg for security
    public function move(): bool     // Uses escapeshellarg for security
    public function delete(): bool
    
    // Encryption operations
    public function moveAndEncrypt(): bool    // Move and encrypt in one operation
    public function encrypt(): false|string   // Returns encrypted file path
    public function decrypt(): false|string   // Returns decrypted file path
    
    // File management
    public function deleteSourceFile(): bool
    public function deleteDestinationFile(): bool
    public function isDestinationDirectoryExists(): bool
    
    // Base64 operations
    public function toBase64File(): false|string
    public function fromBase64ToOrigin(): false|string
    
    // Utility
    public function getFileSize(string $filePath): string  // Returns human-readable size
    
    // File content handling
    protected function readFileContents(): false|string
    protected function writeFileContents(string $contents): bool
}

// Example Usage:
$file = new FileHelper($_FILES['upload']['tmp_name'], 'secure/file.dat');
$file->secret = $encryptionKey;
if ($file->moveAndEncrypt()) {
    echo "File encrypted and stored at: " . $file->outputFile;
} else {
    echo "Error: " . $file->error;
}
```

#### ImageHelper
Advanced image processing with secure file handling.
```php
class ImageHelper {
    public ?string $error = null;
    public string $sourceFile;
    public string $outputFile;

    public function __construct(string $sourceFile, string $outputFile = null)
    
    // Image Processing Methods
    public function convertToWebP(int $quality = 80): bool
    public function setJpegQuality(int $quality = 75): bool
    public function setPngQuality(int $quality = 9): bool

    // File Operations (currently duplicated from FileHelper)
    public function copy(): bool
    public function move(): bool
    public function delete(): bool
    // ... other file operations
}
```

#### StringHelper
Advanced string manipulation with security features.
```php
class StringHelper {
    // Random string generation with specific 59-character set:
    // - Numbers (2-9)
    // - Special characters (_!$%&())
    // - Letters (A-Z, a-z, excluding similar-looking characters)
    public static function randomString(int $stringLength): string

    // URL-friendly string creation with extensive character mapping
    public static function makeWebName(string $string, int $maxLength = 60): string|null
    
    // String sanitization with strict pattern
    public static function sanitizedString(string $incoming_string): string|null
    // Pattern: /^[a-zA-Z0-9_\-\/\(\);,.,äÄöÖüÜß  ]{1,255}$/

    // Validation methods
    public static function isValidUrl(string $url): bool
    public static function isValidEmail(string $email): bool
    public static function safeURL(string $url_string): null|string
    public static function safeEmail(string $emailString): null|string
}
```

#### TypeChecker
Comprehensive type validation system.
```php
class TypeChecker {
    public static function check(mixed $type, mixed $value, array $options = []): bool

    // Internal validation methods
    private static function checkString(mixed $value, array $options): bool
    private static function checkInteger(mixed $value): bool
    private static function checkFloat(mixed $value, array $options): bool
    private static function checkDate(mixed $value, array $options): bool
    private static function checkDateTime(mixed $value, array $options): bool
    private static function checkJson(mixed $value): bool

    // Supported validation options:
    // String: minLength, maxLength, regex
    // Float: min, max
    // Date/DateTime: format
}
```

#### TypeHelper
Type conversion and utility functions.
```php
class TypeHelper {
    // Integer validation
    public static function justInt(mixed $var): null|int
    public static function justIntPositive(mixed $var): null|int

    // Generate GUID
    public static function guid(): string

    // Get current timestamp in Y-m-d H:i:s format
    public static function timeStamp(): string

    // Get non-nullable properties of an object
    public static function getNonNullableProperties(object $object): array

    // Get public methods of a class
    public static function getClassPublicFunctions(string $className, string $exclude = null): array
}
```

#### JsonHelper
JSON manipulation and validation utilities.
```php
class JsonHelper {
    // Validate and decode JSON to array
    public static function validateJsonStringReturnArray(string $jsonStringToValidate): array|null

    // Validate JSON string
    public static function validateJson(mixed $jsonStringToValidate): string|false

    // Validate and decode JSON to object
    public static function validateJsonStringReturnObject(string $jsonStringToValidate): object|null

    // Encode data to JSON
    public static function encodeToJson(mixed $data, int $options = 0): string|false
}
```

#### WebHelper
Server detection with error logging
```php
class WebHelper {
    public static function detectServer(): string
}
```

### Usage Examples

#### Type Validation
```php
// Email validation
if (TypeChecker::check('email', 'user@example.com')) {
    // Valid email
}

// String length validation
if (TypeChecker::check('string', 'password123', ['minLength' => 8])) {
    // String meets minimum length requirement
}
```

#### File Processing
```php
// Secure file upload with encryption
$file = new FileHelper($_FILES['upload']['tmp_name'], 'storage/secure.dat');
$file->secret = $encryptionKey;
if ($file->moveAndEncrypt()) {
    // File successfully encrypted and stored
}

// Image optimization
$image = new ImageHelper($uploadedFile);
$image->convertToWebP(80);
```

#### String Manipulation
```php
// Generate URL-friendly string
$webName = StringHelper::makeWebName("This is a title!", 30);
// Result: "this-is-a-title"

// Generate random string
$token = StringHelper::randomString(16);
```

#### JSON Operations
```php
// Validate and parse JSON
$jsonData = '{"key": "value"}';
if ($data = JsonHelper::validateJsonStringReturnArray($jsonData)) {
    // Valid JSON processed as array
}
```

These helper classes provide a robust foundation for common programming tasks while maintaining security and type safety throughout your application.

## Capabilities & Features

### Database Operations

#### Query Building Examples
```php
// Select with joins
$users = QueryBuilder::select('u.id', 'u.name', 'p.profile_data')
    ->from('users', 'u')
    ->leftJoin('profiles p ON p.user_id = u.id')
    ->whereEqual('u.status', 'active')
    ->orderBy('u.created_at', true)
    ->limit(10)
    ->run($pdoQuery);

// Insert with timestamp
$data = QueryBuilder::insert('logs')
    ->columns('action', 'user_id', 'created_at')
    ->values('login', $userId, TypeHelper::timeStamp())
    ->run($pdoQuery);

// Complex update
$updated = QueryBuilder::update('users')
    ->set('status', 'inactive')
    ->set('updated_at', TypeHelper::timeStamp())
    ->whereEqual('id', $userId)
    ->whereLess('last_login', date('Y-m-d', strtotime('-30 days')))
    ->run($pdoQuery);
```

#### Table Class Examples

The Table class provides a more object-oriented approach to database interactions with built-in ORM capabilities:

```php
// Define a table-specific class
class UserTable extends Table
{
    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public bool $is_active = true;
    public ?string $deleted_at = null;
    
    protected array $_type_map = [
        'id' => 'int',
        'is_active' => 'bool',
        'deleted_at' => 'datetime'
    ];
    
    public function __construct()
    {
        parent::__construct('users');
    }
    
    // Custom methods for user-specific operations
    public function findByEmail(string $email): ?self
    {
        return $this->select()
            ->where('email', $email)
            ->limit(1)
            ->run()[0] ?? null;
    }
    
    public function deactivateExpiredUsers(int $daysInactive): int
    {
        $threshold = date('Y-m-d', strtotime("-{$daysInactive} days"));
        
        return $this->updateQuery(
            "UPDATE {$this->getTable()} SET is_active = 0 
             WHERE last_login < :threshold AND is_active = 1",
            [':threshold' => $threshold]
        ) ?? 0;
    }
}

// Create a new user
$user = new UserTable();
$user->username = 'newuser';
$user->email = 'user@example.com';
$user->password = password_hash('secure123', PASSWORD_DEFAULT);
$result = $user->insertSingleQuery();

// Fetch user by ID with type conversion
$user = (new UserTable())->selectById(123);
if ($user) {
    // $user->id is automatically an integer
    // $user->is_active is automatically a boolean
    // $user->deleted_at is automatically a DateTime object or null
    
    echo "User: {$user->username} (Created: {$user->created_at->format('Y-m-d')})";
}

// Complex query with fluent interface
$recentActiveUsers = (new UserTable())
    ->select()
    ->where('is_active', true)
    ->whereNull('deleted_at')
    ->whereBetween('created_at', 
        date('Y-m-d', strtotime('-30 days')),
        date('Y-m-d')
    )
    ->orderBy('created_at', false) // Descending order
    ->limit(20)
    ->run();

// Working with pagination
$page = $_GET['page'] ?? 1;
$userTable = new UserTable();
$userTable->setPage($page);
$userTable->limit(15);

$users = $userTable
    ->select('id, username, email, created_at')
    ->where('is_active', true)
    ->orderBy('username', true) // Ascending order
    ->run();

// Pagination metadata
$pagination = [
    'current_page' => $userTable->getCurrentPage(),
    'total_pages' => $userTable->getCount(),
    'total_records' => $userTable->getTotalCounts(),
    'per_page' => $userTable->getLimit()
];

// Performance optimization for large tables
$activeCount = (new UserTable())
    ->skipCount() // Skip the COUNT query for better performance
    ->select('COUNT(*) as count')
    ->where('is_active', true)
    ->run();
```

#### Table Generation Examples
```php
// Create a table from a User model
class User {
    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public ?string $bio = null;
    public bool $is_active = true;
    public string $created_at;
    
    // Property to skip (won't be added to database)
    private string $_tempData;
    
    // Get table name
    public function getTable(): string {
        return 'users';
    }
}

// Basic table creation
$generator = new TableGenerator();
if ($generator->createTableFromObject(new User())) {
    echo "Table created successfully!";
}

// Advanced table creation with constraints and indexes
$generator = new TableGenerator();
$generator
    // Add indexes
    ->addIndex('username', true)  // Unique index
    ->addIndex('email', true)     // Unique index
    ->addIndex('created_at')      // Regular index
    
    // Add constraints
    ->setNotNull('username')
    ->setNotNull('email')
    ->setDefault('is_active', true)
    ->setDefault('created_at', 'CURRENT_TIMESTAMP')
    ->addCheck('username', 'LENGTH(username) >= 3')
    
    // Create the table
    ->createTableFromObject(new User());

// Add unique constraint to existing table
$generator = new TableGenerator();
$generator->makeColumnUnique('users', 'email');

// Create composite unique constraint
$generator = new TableGenerator();
$generator->makeColumnsUniqueTogether(
    'user_addresses', 
    ['user_id', 'address_type']
);
```

### File Processing

#### Secure File Handling
```php
// File upload with encryption
$file = new FileHelper(
    $_FILES['upload']['tmp_name'],
    "storage/uploads/" . StringHelper::randomString(16)
);

if ($file->moveAndEncrypt()) {
    $encryptedPath = $file->outputFile;
}

// Image optimization
$image = new ImageHelper($uploadedFile);
$image->convertToWebP(80);
```

## Security Features

### Input Sanitization
- Request sanitization in ApacheRequest
- Query parameter cleaning
- File upload validation

### Cryptographic Operations
- Password hashing with Argon2i
- String encryption with AES-256-CBC
- HMAC verification for encrypted data

### File Operation Security
- Path traversal prevention
- Shell command injection prevention
- MIME type validation

## Quick Start Guide (Expanded)

### 1. Basic Setup
```php
// Install via Composer
composer require gemvc/library

// Create index.php
<?php
require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = new \Dotenv\Dotenv(__DIR__);
$dotenv->load();
```

### 2. Creating Your First API Endpoint
```php
class UserController {
    private PdoConnection $db;
    private JsonResponse $response;

    public function __construct() {
        $this->db = new PdoConnection();
        $this->response = new JsonResponse();
    }

    public function getUsers(ApacheRequest $request): void {
        try {
            // Get query parameters
            $limit = TypeHelper::justIntPositive($request->get['limit']) ?? 10;
            $page = TypeHelper::justIntPositive($request->get['page']) ?? 1;

            // Build and execute query
            $users = QueryBuilder::select('id', 'name', 'email')
                ->from('users')
                ->whereEqual('status', 'active')
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->run($this->db);

            // Return response
            $this->response->success($users)->show();

        } catch (\Exception $e) {
            $this->response->internalError($e->getMessage())->show();
        }
    }
}
```

### 3. Implementing File Upload
```php
public function uploadProfileImage(ApacheRequest $request): void {
    try {
        // Validate file
        if (!isset($request->files['profile_image'])) {
            throw new ValidationException('No file uploaded');
        }

        $file = $request->files['profile_image'];
        $uploadDir = 'storage/profiles/';
        $fileName = StringHelper::randomString(16) . '.webp';

        // Process image
        $image = new ImageHelper($file['tmp_name'], $uploadDir . $fileName);
        if (!$image->convertToWebP(80)) {
            throw new ImageProcessingException($image->error);
        }

        $this->response->success(['file_path' => $uploadDir . $fileName])->show();

    } catch (\Exception $e) {
        $this->response->badRequest($e->getMessage())->show();
    }
}
```

### 4. Secure Authentication Example
```php
class AuthController {
    public function login(ApacheRequest $request): void {
        try {
            $email = StringHelper::safeEmail($request->post['email']);
            $password = $request->post['password'];

            if (!$email) {
                throw new ValidationException('Invalid email format');
            }

            // Verify credentials
            $user = $this->verifyCredentials($email, $password);
            
            // Generate tokens
            $accessToken = CryptHelper::generateToken($user, 'access');
            $refreshToken = CryptHelper::generateToken($user, 'refresh');

            $this->response->success([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken
            ])->show();

        } catch (\Exception $e) {
            $this->response->unauthorized($e->getMessage())->show();
        }
    }
}
```

## API Reference

### Database Layer

#### PdoConnection
```php
class PdoConnection {
    /**
     * Establishes database connection
     * @return PDO|null PDO instance or null on failure
     * @throws DatabaseException on connection error
     */
    public function connect(): \PDO|null

    /**
     * Checks connection status
     * @return bool True if connected
     */
    public function isConnected(): bool

    /**
     * Returns last error message
     * @return string|null Error message or null
     */
    public function getError(): null|string
}
```

#### Table
```php
class Table extends PdoQuery {
    /**
     * Initialize a new Table instance
     * @param string $tableName Database table name (required)
     * @throws \InvalidArgumentException if table name is empty
     */
    public function __construct(string $tableName)
    
    /**
     * Inserts a single row into the database table
     * @return null|static The current instance with inserted id or null on failure
     */
    public function insertSingleQuery(): null|static
    
    /**
     * Updates a record based on its ID property
     * @return null|static Current instance or null on failure
     */
    public function updateSingleQuery(): null|static
    
    /**
     * Deletes a record by ID
     * @param int $id Record ID to delete
     * @return null|int ID of deleted record or null on failure
     */
    public function deleteByIdQuery(int $id): null|int
    
    /**
     * Marks a record as deleted (soft delete)
     * @return null|static Current instance or null on failure
     */
    public function safeDeleteQuery(): null|static
    
    /**
     * Restores a soft-deleted record
     * @return null|static Current instance or null on failure
     */
    public function restoreQuery(): null|static
    
    /**
     * Starts building a SELECT query
     * @param string|null $columns Columns to select (defaults to *)
     * @return self For method chaining
     */
    public function select(string $columns = null): self
    
    /**
     * Adds a WHERE condition
     * @param string $column Column name
     * @param mixed $value Value to match
     * @return self For method chaining
     */
    public function where(string $column, mixed $value): self
    
    /**
     * Executes a SELECT query and returns results
     * @return array<static>|null Array of model instances or null on failure
     */
    public function run(): null|array
    
    /**
     * Selects a single row by ID
     * @param int $id Record ID to select
     * @return null|static Found instance or null if not found
     */
    public function selectById(int $id): null|static
    
    /**
     * Disables count subqueries for performance optimization
     * @return self For method chaining
     */
    public function skipCount(): self
}
```

#### QueryBuilder
```php
class QueryBuilder {
    /**
     * Creates SELECT query
     * @param string ...$select Columns to select
     * @return Select Query builder instance
     */
    public static function select(string ...$select): Select

    /**
     * Creates INSERT query
     * @param string $table Target table
     * @return Insert Query builder instance
     */
    public static function insert(string $table): Insert
}
```

#### QueryExecuter
```php
class QueryExecuter {
    /**
     * Constructor initializes execution timer but doesn't establish a database connection
     * Connection is only established when needed for query execution
     */
    public function __construct()
    
    /**
     * Destructor ensures resources are properly cleaned up
     */
    public function __destruct()
    
    /**
     * Lazy-loads the database connection when needed
     */
    private function ensureConnection(): bool
    
    /**
     * Prepare an SQL query for execution
     * Establishes a database connection if needed
     */
    public function query(string $query): void
    
    /**
     * Execute the prepared statement
     * Establishes a database connection if needed
     */
    public function execute(): bool
    
    /**
     * Release database resources and close connection
     */
    public function secure(): void
    
    // Result fetching methods
    public function fetchAll(): array|false
    public function fetchAllObjects(): array|false
    public function fetchAllClass(string $targetClassName): array|false
    public function fetchColumn(): mixed
    
    // Metadata methods
    public function affectedRows(): int|null
    public function lastInsertId(): string|false
    public function getExecutionTime(): float
    public function getError(): ?string
}
```

#### TableGenerator
```php
class TableGenerator extends QueryExecuter {
    /**
     * Creates a table from an object
     * @param object $object The object to create a table from
     * @param string|null $tableName Table name or null to use object's getTable() method
     * @return bool Success status
     */
    public function createTableFromObject(object $object, string $tableName = null): bool
    
    /**
     * Makes a column in an existing table unique
     * @param string $tableName The table name
     * @param string $columnName The column to make unique
     * @param bool $dropExistingIndex Whether to drop existing indexes
     * @return bool Success status
     */
    public function makeColumnUnique(string $tableName, string $columnName, bool $dropExistingIndex = true): bool
    
    /**
     * Creates a composite unique constraint
     * @param string $tableName The table name
     * @param array $columnNames Array of column names to make unique together
     * @param string|null $indexName Optional custom index name
     * @param bool $dropExistingIndexes Whether to drop existing indexes
     * @return bool Success status
     */
    public function makeColumnsUniqueTogether(string $tableName, array $columnNames, ?string $indexName = null, bool $dropExistingIndexes = true): bool
    
    /**
     * Marks a property for indexing
     * @param string $propertyName The property to index
     * @param bool $unique Whether index should be unique
     * @return self For method chaining
     */
    public function addIndex(string $propertyName, bool $unique = false): self
    
    /**
     * Removes indexing from a property
     * @param string $propertyName The property to remove indexing from
     * @return self For method chaining
     */
    public function removeIndex(string $propertyName): self
    
    /**
     * Sets custom SQL properties for a column
     * @param string $propertyName The property name
     * @param string $columnProperties The SQL properties to add
     * @return self For method chaining
     */
    public function setColumnProperties(string $propertyName, string $columnProperties): self
    
    /**
     * Makes a column NOT NULL
     * @param string $propertyName The property name
     * @return self For method chaining
     */
    public function setNotNull(string $propertyName): self
    
    /**
     * Sets a default value for a column
     * @param string $propertyName The property name
     * @param mixed $defaultValue The default value
     * @return self For method chaining
     */
    public function setDefault(string $propertyName, mixed $defaultValue): self
    
    /**
     * Adds a CHECK constraint to a column
     * @param string $propertyName The property name
     * @param string $checkExpression The SQL expression for the check
     * @return self For method chaining
     */
    public function addCheck(string $propertyName, string $checkExpression): self
    
    /**
     * Removes a column from an existing table
     * @param string $tableName The table name
     * @param string $columnName The column to remove
     * @return bool Success status
     */
    public function removeColumn(string $tableName, string $columnName): bool
    
    /**
     * Updates an existing table based on changes in object properties
     * @param object $object The object with updated properties
     * @param string|null $tableName Table name or null to use object's getTable() method
     * @param bool $removeExtraColumns Whether to remove columns that don't exist in the object
     * @return bool Success status
     */
    public function updateTable(object $object, string $tableName = null, bool $removeExtraColumns = false): bool
}
```

Type mapping:
- PHP `int`/`integer` → SQL `INT(11)`
- PHP `float`/`double` → SQL `DOUBLE`
- PHP `bool`/`boolean` → SQL `TINYINT(1)`
- PHP `string` → SQL `VARCHAR(255)`
- PHP `datetime` → SQL `DATETIME`
- PHP `array` → SQL `JSON`
- Unknown types → SQL `TEXT`

Special field handling:
- Property named `id` → Auto-increment primary key
- Properties ending with `_id` → `INT(11)` (for foreign keys)
- Properties ending with `email` → `VARCHAR(320)` (max email length)

Properties that are skipped:
- Static properties
- Properties that start with underscore (_)
- Constants (read-only public properties in PHP 8.1+)

Key features:
- **PHP to SQL Type Mapping**: Automatically maps PHP types to appropriate SQL column types
- **Property Filtering**: Skips properties that shouldn't be database columns (static, underscore-prefixed, constants)
- **Special Field Handling**: Automatic handling for ID fields, foreign keys, and email addresses
- **Index Management**: Support for regular and unique indexes, including composite indexes
- **Column Constraints**: Support for NOT NULL, DEFAULT values, and CHECK constraints
- **Transaction Support**: All operations wrapped in transactions for atomicity
- **Schema Management**: Ability to update existing tables by adding new columns, modifying changed columns, and removing deleted columns

### HTTP Layer

#### ApacheRequest
```php
class ApacheRequest {
    /**
     * Gets sanitized POST data
     * @return array Sanitized POST data
     */
    public function getPost(): array

    /**
     * Gets sanitized GET parameters
     * @return array Sanitized GET parameters
     */
    public function getQuery(): array
}
```

#### JsonResponse
```php
class JsonResponse {
    // Success responses
    public function success(mixed $data, int $count = null, string $service_message = null): JsonResponse
    public function created(mixed $data): JsonResponse
    public function updated(mixed $data): JsonResponse
    public function deleted(mixed $data): JsonResponse
    
    // Error responses
    public function badRequest(string $message = null): JsonResponse
    public function unauthorized(): JsonResponse
    public function forbidden(): JsonResponse
    public function notFound(): JsonResponse
    public function internalError(string $message = null): JsonResponse
}
```

### Helper Utilities

#### FileHelper
```php
class FileHelper {
    /**
     * Moves and encrypts file
     * @return bool Success status
     */
    public function moveAndEncrypt(): bool

    /**
     * Gets human-readable file size
     * @param string $filePath Path to file
     * @return string Formatted size
     */
    public function getFileSize(string $filePath): string
}
```

#### ImageHelper
```php
class ImageHelper {
    // Image Processing Methods
    public function convertToWebP(int $quality = 80): bool
    public function setJpegQuality(int $quality = 75): bool
    public function setPngQuality(int $quality = 9): bool
}
```

#### StringHelper
```php
class StringHelper {
    // Random string generation with specific 59-character set:
    // - Numbers (2-9)
    // - Special characters (_!$%&())
    // - Letters (A-Z, a-z, excluding similar-looking characters)
    public static function randomString(int $stringLength): string

    // URL-friendly string creation with extensive character mapping
    public static function makeWebName(string $string, int $maxLength = 60): string|null
    
    // String sanitization with strict pattern
    public static function sanitizedString(string $incoming_string): string|null
    // Pattern: /^[a-zA-Z0-9_\-\/\(\);,.,äÄöÖüÜß  ]{1,255}$/

    // Validation methods
    public static function isValidUrl(string $url): bool
    public static function isValidEmail(string $email): bool
    public static function safeURL(string $url_string): null|string
    public static function safeEmail(string $emailString): null|string
}
```

#### TypeChecker
```php
class TypeChecker {
    public static function check(mixed $type, mixed $value, array $options = []): bool

    // Internal validation methods
    private static function checkString(mixed $value, array $options): bool
    private static function checkInteger(mixed $value): bool
    private static function checkFloat(mixed $value, array $options): bool
    private static function checkDate(mixed $value, array $options): bool
    private static function checkDateTime(mixed $value, array $options): bool
    private static function checkJson(mixed $value): bool

    // Supported validation options:
    // String: minLength, maxLength, regex
    // Float: min, max
    // Date/DateTime: format
}
```

#### TypeHelper
```php
class TypeHelper {
    // Integer validation
    public static function justInt(mixed $var): null|int
    public static function justIntPositive(mixed $var): null|int

    // Generate GUID
    public static function guid(): string

    // Get current timestamp in Y-m-d H:i:s format
    public static function timeStamp(): string

    // Get non-nullable properties of an object
    public static function getNonNullableProperties(object $object): array

    // Get public methods of a class
    public static function getClassPublicFunctions(string $className, string $exclude = null): array
}
```

#### JsonHelper
```php
class JsonHelper {
    // Validate and decode JSON to array
    public static function validateJsonStringReturnArray(string $jsonStringToValidate): array|null

    // Validate JSON string
    public static function validateJson(mixed $jsonStringToValidate): string|false

    // Validate and decode JSON to object
    public static function validateJsonStringReturnObject(string $jsonStringToValidate): object|null

    // Encode data to JSON
    public static function encodeToJson(mixed $data, int $options = 0): string|false
}
```

#### WebHelper
```php
class WebHelper {
    public static function detectServer(): string
}
```

### Email System

#### GemSMTP
- Secure SMTP configuration with TLS/SSL
- File size limits:
  - MAX_FILE_SIZE: 10MB
  - MAX_CONTENT_SIZE: 25MB
- Retry mechanism with MAX_RETRIES (3)
- Content security validation
- Support for 45 languages

## Best Practices

### 1. Database Operations

#### Query Optimization
```php
// DO: Use specific column names
QueryBuilder::select('id', 'name', 'email')
    ->from('users');

// DON'T: Use SELECT *
QueryBuilder::select('*')
    ->from('users');

// DO: Use prepared statements (automatic with QueryBuilder)
QueryBuilder::select('id')
    ->whereEqual('email', $email);

// DO: Implement pagination
QueryBuilder::select('id', 'name')
    ->limit($limit)
    ->offset($offset);
```

#### Transaction Management
```php
try {
    $pdo->beginTransaction();
    
    // Multiple operations
    $query1->run($pdo);
    $query2->run($pdo);
    
    $pdo->commit();
} catch (\Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

### 2. Security Best Practices

#### Input Validation
```php
// DO: Validate and sanitize all inputs
$email = StringHelper::safeEmail($request->post['email']);
$name = StringHelper::sanitizedString($request->post['name']);

// DO: Use type checking
if (!TypeChecker::check('email', $email)) {
    throw new ValidationException('Invalid email');
}
```

#### File Handling
```php
// DO: Validate file types
$allowedTypes = ['image/jpeg', 'image/png'];
if (!in_array($file['type'], $allowedTypes)) {
    throw new ValidationException('Invalid file type');
}

// DO: Use secure file operations
$file = new FileHelper($sourcePath);
$file->secret = $encryptionKey;
$file->moveAndEncrypt();
```

### 3. Error Handling
```php
// DO: Use structured error handling
try {
    // Operation
} catch (DatabaseException $e) {
    ErrorLogger::logError('Database', $e->getMessage());
    throw $e;
} catch (ValidationException $e) {
    // Handle validation errors
} catch (\Exception $e) {
    // Handle unexpected errors
}
```

## Performance Optimization

### 1. Database Optimization

#### Query Caching
```php
class QueryCache {
    private static $cache = [];

    public static function get(string $key) {
        return self::$cache[$key] ?? null;
    }

    public static function set(string $key, $value, int $ttl = 300): void {
        self::$cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
    }
}

// Usage
$cacheKey = 'users_active';
$users = QueryCache::get($cacheKey);

if (!$users) {
    $users = QueryBuilder::select('id', 'name')
        ->from('users')
        ->whereEqual('status', 'active')
        ->run($pdo);
        
    QueryCache::set($cacheKey, $users);
}
```

#### Connection Pooling
```php
class ConnectionPool {
    private static $connections = [];
    private static $maxConnections = 10;

    public static function getConnection(): \PDO {
        // Reuse existing connection if available
        foreach (self::$connections as $conn) {
            if (!$conn['in_use']) {
                $conn['in_use'] = true;
                return $conn['connection'];
            }
        }

        // Create new connection if limit not reached
        if (count(self::$connections) < self::$maxConnections) {
            $pdo = new PdoConnection();
            self::$connections[] = [
                'connection' => $pdo,
                'in_use' => true
            ];
            return $pdo;
        }

        throw new DatabaseException('Connection pool exhausted');
    }
}
```

### 2. File Operation Optimization

#### Streaming Large Files
```php
class FileStreamer {
    public static function stream(string $filePath): void {
        $handle = fopen($filePath, 'rb');
        $bufferSize = 8192;

        while (!feof($handle)) {
            echo fread($handle, $bufferSize);
            flush();
        }

        fclose($handle);
    }
}
```

#### Batch Processing
```php
class BatchProcessor {
    public static function processImages(array $files, int $batchSize = 10): array {
        $results = [];
        $batches = array_chunk($files, $batchSize);

        foreach ($batches as $batch) {
            foreach ($batch as $file) {
                $image = new ImageHelper($file);
                $results[] = $image->convertToWebP();
            }
            // Free up memory after each batch
            gc_collect_cycles();
        }

        return $results;
    }
}
```

## Troubleshooting

### Common Error Patterns

#### 1. Database Connection Issues

```php
try {
    $pdo = new PdoConnection();
    if (!$pdo->isConnected()) {
        // Handle connection error
        $error = $pdo->getError();
        error_log("Database connection failed: " . $error);
        throw new DatabaseException($error);
    }
} catch (DatabaseException $e) {
    // Log the error and return appropriate response
    $response = new JsonResponse();
    return $response->internalError("Database connection error");
}
```

Common Solutions:
- Verify database credentials in `.env`