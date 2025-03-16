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

# Authentication
TOKEN_SECRET='secret for your token'
TOKEN_ISSUER='your_api_name'
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
ACCESS_TOKEN_VALIDATION_IN_SECONDS=15800

# URL Configuration
SERVICE_IN_URL_SECTION=2
METHOD_IN_URL_SECTION=3
```

## Core Components

### 1. Database Layer

#### PdoConnection
Handles database connectivity and state management.
```php
class PdoConnection {
    public function connect(): \PDO|null
    public function isConnected(): bool
    public function getError(): null|string
    public function db(): \PDO|null
}
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

### 2. HTTP Layer

#### ApacheRequest
Handles incoming HTTP requests with automatic sanitization.
```php
class ApacheRequest {
    public Request $request;
    
    // Available request data
    $request->post;      // POST data
    $request->get;       // GET parameters
    $request->files;     // Uploaded files
    $request->put;       // PUT data
    $request->patch;     // PATCH data
}
```

#### JsonResponse
Standardized JSON response handling.
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
if ($image->convertToWebP(80)) {
    // Image converted to WebP format
}
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