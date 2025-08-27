# Testing Framework - PHPUnit or Pest Integration

## Overview

GEMVC offers a choice between two popular PHP testing frameworks during project initialization:
- **PHPUnit**: Traditional, battle-tested testing framework
- **Pest**: Modern, expressive testing framework with elegant syntax

## Framework Choice

During `gemvc init`, you'll be presented with a choice:

```
Choose testing framework:
  [1] PHPUnit - Traditional PHP testing framework
  [2] Pest - Modern, expressive testing framework
  [3] Skip - No testing framework
```

## PHPUnit Integration

### Features
- **Traditional Approach**: Familiar to most PHP developers
- **Comprehensive**: Full-featured testing framework
- **XML Configuration**: Flexible configuration via `phpunit.xml`
- **Coverage Reports**: Built-in code coverage analysis

### Installation
```bash
composer require --dev phpunit/phpunit
```

### Configuration
A pre-configured `phpunit.xml` file is created:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="GEMVC Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">app</directory>
        </include>
    </coverage>
</phpunit>
```

### Composer Scripts
```json
{
    "scripts": {
        "test": "phpunit",
        "test:coverage": "phpunit --coverage-html coverage"
    }
}
```

### Usage
```bash
# Run all tests
composer test

# Run with coverage report
composer test:coverage

# Run specific test file
./vendor/bin/phpunit tests/UserTest.php

# Run with specific configuration
./vendor/bin/phpunit --configuration phpunit.xml
```

### Example Test
```php
<?php

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation()
    {
        $user = new User('John Doe', 'john@example.com');
        
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john@example.com', $user->getEmail());
    }
    
    public function testUserValidation()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new User('', 'invalid-email');
    }
}
```

## Pest Integration

### Features
- **Modern Syntax**: Elegant, readable test syntax
- **Expressiveness**: More natural language for test descriptions
- **Auto-discovery**: Automatic test discovery and execution
- **Parallel Testing**: Built-in support for parallel test execution

### Installation
```bash
composer require --dev pestphp/pest
```

### Initialization
Pest automatically runs `pest --init` to set up the testing environment.

### Composer Scripts
```json
{
    "scripts": {
        "test": "pest",
        "test:parallel": "pest --parallel"
    }
}
```

### Usage
```bash
# Run all tests
composer test

# Run tests in parallel
composer test:parallel

# Run specific test file
./vendor/bin/pest tests/UserTest.php

# Run with filters
./vendor/bin/pest --filter="user creation"
```

### Example Test
```php
<?php

use function Pest\Laravel\get;

test('user can be created', function () {
    $user = new User('John Doe', 'john@example.com');
    
    expect($user->getName())->toBe('John Doe');
    expect($user->getEmail())->toBe('john@example.com');
});

test('user validation throws exception for invalid data', function () {
    expect(fn() => new User('', 'invalid-email'))
        ->toThrow(InvalidArgumentException::class);
});

describe('User Authentication', function () {
    test('can authenticate with valid credentials', function () {
        $user = new User('John Doe', 'john@example.com');
        
        expect($user->authenticate('password123'))->toBeTrue();
    });
    
    test('cannot authenticate with invalid credentials', function () {
        $user = new User('John Doe', 'john@example.com');
        
        expect($user->authenticate('wrongpassword'))->toBeFalse();
    });
});
```

## Directory Structure

Both frameworks create a `tests/` directory in your project root:

```
tests/
├── Unit/           # Unit tests
├── Feature/        # Feature/integration tests
├── Integration/    # Integration tests
└── TestCase.php    # Base test case (if applicable)
```

## Framework Comparison

| Feature | PHPUnit | Pest |
|---------|---------|------|
| **Syntax** | Traditional PHP | Modern, expressive |
| **Learning Curve** | Low (familiar) | Medium (new concepts) |
| **Configuration** | XML-based | PHP-based |
| **Parallel Testing** | Requires plugins | Built-in |
| **Community** | Large, mature | Growing, active |
| **IDE Support** | Excellent | Good |
| **CI/CD Integration** | Excellent | Good |

## Best Practices

### General Testing
1. **Test Structure**: Follow AAA pattern (Arrange, Act, Assert)
2. **Test Names**: Use descriptive test method names
3. **Isolation**: Each test should be independent
4. **Coverage**: Aim for meaningful coverage, not just percentage

### PHPUnit Specific
1. **Test Classes**: Extend `TestCase` class
2. **Assertions**: Use specific assertion methods
3. **Data Providers**: Use for testing multiple scenarios
4. **Mocking**: Use PHPUnit's built-in mocking capabilities

### Pest Specific
1. **Test Functions**: Use `test()` function for simple tests
2. **Describe Blocks**: Group related tests logically
3. **Expectations**: Use `expect()` for readable assertions
4. **Parallel Execution**: Use `--parallel` for faster execution

## Integration with GEMVC

### Testing Controllers
```php
<?php

use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

test('user controller returns users list', function () {
    $request = new Request();
    $controller = new UserController($request);
    
    $response = $controller->list();
    
    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getData())->toHaveKey('users');
});
```

### Testing Models
```php
<?php

test('user model validates email format', function () {
    $user = new UserModel();
    
    expect($user->validateEmail('valid@email.com'))->toBeTrue();
    expect($user->validateEmail('invalid-email'))->toBeFalse();
});
```

### Testing Database Operations
```php
<?php

use Gemvc\Database\Table;

test('user table can create new user', function () {
    $table = new UserTable();
    
    $userId = $table->create([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
    
    expect($userId)->toBeGreaterThan(0);
    
    $user = $table->find($userId);
    expect($user['name'])->toBe('John Doe');
});
```

## CI/CD Integration

### GitHub Actions Example
```yaml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: composer install
      - run: composer test
      - run: composer phpstan
```

### GitLab CI Example
```yaml
test:
  stage: test
  script:
    - composer install
    - composer test
    - composer phpstan
  coverage: '/Code Coverage: \d+\.\d+%/'
```

## Troubleshooting

### Common Issues

1. **Test Discovery**: Ensure test files follow naming conventions
2. **Autoloading**: Verify `composer.json` autoload configuration
3. **Database Connections**: Use test-specific database configurations
4. **Environment Variables**: Set up test environment properly

### Performance Optimization

1. **Parallel Execution**: Use Pest's parallel testing when possible
2. **Test Isolation**: Avoid unnecessary setup/teardown operations
3. **Database**: Use in-memory databases for faster tests
4. **Caching**: Disable caching during tests

## Migration Between Frameworks

### PHPUnit to Pest
1. Install Pest: `composer require --dev pestphp/pest`
2. Convert test methods to Pest syntax
3. Update CI/CD configurations
4. Remove PHPUnit-specific code

### Pest to PHPUnit
1. Install PHPUnit: `composer require --dev phpunit/phpunit`
2. Convert Pest tests to PHPUnit format
3. Create `phpunit.xml` configuration
4. Update CI/CD configurations

---

*Choose the testing framework that best fits your team's experience and project requirements. Both frameworks integrate seamlessly with GEMVC and provide excellent testing capabilities.*
