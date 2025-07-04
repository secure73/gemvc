# Testing Tools

## Overview

GEMVC provides a comprehensive testing framework that supports unit testing, integration testing, and API testing. The framework is designed to work with PHPUnit and provides additional testing utilities specific to GEMVC applications.

## Testing Structure

### Directory Organization
```
tests/
├── Unit/           # Unit tests
├── Integration/    # Integration tests
├── Feature/        # Feature/API tests
├── Helpers/        # Testing helpers
└── bootstrap.php   # Test bootstrap file
```

### Test Categories

#### Unit Tests
- Test individual classes and methods
- Mock dependencies
- Fast execution
- Focus on business logic

#### Integration Tests
- Test component interactions
- Database operations
- Service layer testing
- Real dependencies

#### Feature Tests
- Test complete API endpoints
- HTTP request/response testing
- Authentication testing
- End-to-end scenarios

## Setup

### Installation
```bash
composer require --dev phpunit/phpunit
```

### Configuration
Create `phpunit.xml` in your project root:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./src</directory>
        </include>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_DATABASE" value="test_database"/>
    </php>
</phpunit>
```

### Bootstrap File
Create `tests/bootstrap.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_DATABASE'] = 'test_database';

// Load test helpers
require_once __DIR__ . '/Helpers/TestHelper.php';
```

## Writing Tests

### Unit Tests

#### Controller Testing
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use App\Controllers\UserController;

class UserControllerTest extends TestCase
{
    private UserController $controller;
    private Request $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->controller = new UserController($this->request);
    }

    public function testIndexReturnsSuccessResponse(): void
    {
        $response = $this->controller->index();
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShowReturnsUserData(): void
    {
        $userId = 1;
        $response = $this->controller->show($userId);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

#### Model Testing
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use Gemvc\Database\PdoQuery;

class UserTest extends TestCase
{
    private User $user;
    private PdoQuery $pdoQuery;

    protected function setUp(): void
    {
        $this->pdoQuery = $this->createMock(PdoQuery::class);
        $this->user = new User();
        $this->user->pdoQuery = $this->pdoQuery;
    }

    public function testFindByEmailReturnsUser(): void
    {
        $email = 'test@example.com';
        $expectedUser = ['id' => 1, 'email' => $email];
        
        $this->pdoQuery->method('fetch')
            ->willReturn($expectedUser);
        
        $result = $this->user->findByEmail($email);
        
        $this->assertEquals($expectedUser, $result);
    }

    public function testCreateUserSavesToDatabase(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];
        
        $this->pdoQuery->method('execute')
            ->willReturn(true);
        
        $result = $this->user->create($userData);
        
        $this->assertTrue($result);
    }
}
```

### Integration Tests

#### Service Testing
```php
<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Gemvc\Http\Request;
use App\Services\UserService;
use Gemvc\Http\JsonResponse;

class UserServiceTest extends TestCase
{
    private UserService $service;
    private Request $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->service = new UserService($this->request);
    }

    public function testGetUsersReturnsUserList(): void
    {
        $response = $this->service->getUsers();
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateUserValidatesInput(): void
    {
        // Mock request data
        $this->request->method('post')
            ->willReturn([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]);
        
        $response = $this->service->createUser();
        
        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
```

### Feature Tests

#### API Endpoint Testing
```php
<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use App\Controllers\UserController;

class UserApiTest extends TestCase
{
    private UserController $controller;

    protected function setUp(): void
    {
        $request = new Request();
        $this->controller = new UserController($request);
    }

    public function testGetUsersEndpoint(): void
    {
        $response = $this->controller->index();
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
    }

    public function testCreateUserEndpoint(): void
    {
        // Simulate POST data
        $_POST = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];
        
        $response = $this->controller->store();
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }
}
```

## Testing Helpers

### TestHelper Class
```php
<?php

namespace Tests\Helpers;

use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class TestHelper
{
    public static function createMockRequest(array $data = []): Request
    {
        $request = new Request();
        
        if (!empty($data)) {
            $_POST = $data;
        }
        
        return $request;
    }

    public static function assertJsonResponse(JsonResponse $response, int $statusCode = 200): void
    {
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($statusCode, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertNotNull($data);
    }

    public static function assertSuccessResponse(JsonResponse $response): void
    {
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success'] ?? false);
    }

    public static function assertErrorResponse(JsonResponse $response): void
    {
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success'] ?? true);
        $this->assertArrayHasKey('error', $data);
    }
}
```

## Database Testing

### Test Database Setup
```php
<?php

namespace Tests\Helpers;

use Gemvc\Database\PdoQuery;

class DatabaseTestHelper
{
    private PdoQuery $pdoQuery;

    public function __construct()
    {
        $this->pdoQuery = new PdoQuery();
    }

    public function setUpTestDatabase(): void
    {
        // Create test database tables
        $this->createTestTables();
        
        // Seed test data
        $this->seedTestData();
    }

    public function tearDownTestDatabase(): void
    {
        // Clean up test data
        $this->cleanupTestData();
    }

    private function createTestTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        $this->pdoQuery->execute($sql);
    }

    private function seedTestData(): void
    {
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $this->pdoQuery->execute($sql, ['Test User', 'test@example.com', 'password123']);
    }

    private function cleanupTestData(): void
    {
        $this->pdoQuery->execute("DELETE FROM users");
    }
}
```

## Authentication Testing

### JWT Token Testing
```php
<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Gemvc\Http\JWTToken;
use Gemvc\Http\Request;

class AuthenticationTest extends TestCase
{
    public function testValidTokenAuthentication(): void
    {
        $token = JWTToken::generate(['user_id' => 1, 'role' => 'user']);
        
        $request = new Request();
        $request->headers['Authorization'] = 'Bearer ' . $token;
        
        $this->assertTrue($request->auth(['user']));
        $this->assertEquals(1, $request->userId());
        $this->assertEquals('user', $request->userRole());
    }

    public function testInvalidTokenAuthentication(): void
    {
        $request = new Request();
        $request->headers['Authorization'] = 'Bearer invalid_token';
        
        $this->assertFalse($request->auth(['user']));
    }

    public function testMissingTokenAuthentication(): void
    {
        $request = new Request();
        
        $this->assertFalse($request->auth(['user']));
    }
}
```

## Running Tests

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test Suite
```bash
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration
./vendor/bin/phpunit --testsuite Feature
```

### Run Specific Test File
```bash
./vendor/bin/phpunit tests/Unit/UserControllerTest.php
```

### Run with Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

## Best Practices

### 1. Test Organization
- Group related tests in the same class
- Use descriptive test method names
- Follow the AAA pattern (Arrange, Act, Assert)
- Keep tests independent and isolated

### 2. Mocking
- Mock external dependencies
- Mock database connections in unit tests
- Use real dependencies in integration tests
- Avoid over-mocking

### 3. Test Data
- Use factories for test data creation
- Clean up test data after each test
- Use meaningful test data
- Avoid hardcoded values

### 4. Assertions
- Test one thing per test method
- Use specific assertions
- Test both success and failure cases
- Test edge cases and error conditions

### 5. Performance
- Keep unit tests fast
- Use database transactions for integration tests
- Avoid unnecessary setup/teardown
- Use test doubles when appropriate

## Continuous Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: test_database
        options: >-
          --health-cmd "mysqladmin ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 3
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: pdo_mysql, openssl, gd
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run tests
      run: ./vendor/bin/phpunit --coverage-clover coverage.xml
    
    - name: Upload coverage
      uses: codecov/codecov-action@v1
      with:
        file: ./coverage.xml
```

## Next Steps

- [Getting Started](../getting-started/quick-start.md)
- [Database Architecture](../core/database-architecture.md)
- [API Documentation](../features/api-documentation.md) 