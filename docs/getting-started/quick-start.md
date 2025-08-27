# Quick Start Guide

## Overview

This guide will walk you through setting up a new GEMVC project with all the modern development tools and best practices.

## Prerequisites

- PHP 8.0 or higher
- Composer
- Docker and Docker Compose (for development environment)

## Step 1: Install GEMVC

```bash
composer require gemvc/library
```

## Step 2: Initialize Your Project

```bash
vendor/bin/gemvc init
```

This command will:

1. **Create Project Structure**: Set up the complete directory structure
2. **Choose Server Template**: Select between Apache or OpenSwoole
3. **Copy Configuration Files**: Set up environment and server configurations
4. **Create Command Wrappers**: Set up local `bin/gemvc` command
5. **Offer PHPStan Installation**: Optional static analysis tool for code quality
6. **Choose Testing Framework**: Select between PHPUnit (traditional) or Pest (modern)

### Project Initialization Flow

```
╭─ GEMVC Project Initialization ──────────────────────────────────╮
│                                                                 │
│ 1. Creating directory structure...                              │
│ 2. Copying server templates...                                  │
│ 3. Setting up environment...                                    │
│ 4. Creating command wrappers...                                  │
│                                                                 │
│ ╭─ Next Steps ───────────────────────────────────────────────╮ │
│ │ Development: composer update                                │ │
│ │ Production: composer update --no-dev --prefer-dist         │ │
│ ╰─────────────────────────────────────────────────────────────╯ │
│                                                                 │
│ ╭─ PHPStan Installation ───────────────────────────────────────╮ │
│ │ Would you like to install PHPStan for static analysis?      │ │
│ │ PHPStan will help catch bugs and improve code quality       │ │
│ ╰───────────────────────────────────────────────────────────────╯ │
│                                                                 │
│ ╭─ Testing Framework Installation ───────────────────────────────╮ │
│ │ Choose between PHPUnit (traditional) or Pest (modern)        │ │
│ │ Both provide comprehensive testing capabilities               │ │
│ ╰──────────────────────────────────────────────────────────────────╯ │
│                                                                 │
╰─────────────────────────────────────────────────────────────────╯
```

## Step 3: Development Environment Setup

### Start Docker Services

```bash
docker-compose up --build
```

This starts:
- **Web Server**: Port 80 (Apache) or 9501 (OpenSwoole)
- **MySQL Database**: Port 3306
- **PHPMyAdmin**: Port 8080
- **Redis**: Port 6379

### Database Access

- **PHPMyAdmin**: http://localhost:8080
  - Username: `root`
  - Password: `rootpassword`
- **MySQL Direct**: localhost:3306

## Step 4: Install Dependencies

```bash
# Development environment (includes testing tools)
composer update

# Production environment (optimized)
composer update --no-dev --prefer-dist --optimize-autoloader
```

## Step 5: Configure Environment

Edit `app/.env` file:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database
DB_USER=root
DB_PASSWORD=rootpassword

# Security Settings
TOKEN_SECRET=your_secret_key_here
TOKEN_ISSUER=your_api_name

# Server Configuration
SERVER_PORT=80  # or 9501 for Swoole
```

## Step 6: Create Your First API

### Create API Service

```php
<?php
// app/api/User.php

namespace App\Api;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class User extends ApiService 
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    
    public function getUsers(): JsonResponse 
    {
        // Your API logic here
        return new JsonResponse([
            'users' => [
                ['id' => 1, 'name' => 'John Doe'],
                ['id' => 2, 'name' => 'Jane Smith']
            ]
        ]);
    }
}
```

### Create Controller

```php
<?php
// app/controller/UserController.php

namespace App\Controller;

use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class UserController 
{
    private Request $request;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    public function list(): JsonResponse 
    {
        // Business logic here
        return new JsonResponse(['message' => 'Users list']);
    }
}
```

## Step 7: Run Tests

### If you chose PHPUnit:

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run specific test
./vendor/bin/phpunit tests/UserTest.php
```

### If you chose Pest:

```bash
# Run all tests
composer test

# Run in parallel
composer test:parallel

# Run specific test
./vendor/bin/pest tests/UserTest.php
```

## Step 8: Code Quality Analysis

### Run PHPStan (if installed):

```bash
# Basic analysis
composer phpstan

# JSON output for CI/CD
composer phpstan:check

# Custom level
./vendor/bin/phpstan analyse --level=5
```

## Step 9: Access API Documentation

Visit `http://localhost/document` to access the interactive API documentation.

## Step 10: Development Workflow

### Daily Development Cycle:

1. **Write Code**: Create your API services, controllers, and models
2. **Run Tests**: Ensure all tests pass
3. **Code Quality**: Run PHPStan for static analysis
4. **Commit**: Use meaningful commit messages
5. **Deploy**: Use your preferred deployment method

### Recommended Commands:

```bash
# Development workflow
composer test          # Run tests
composer phpstan       # Static analysis
composer update        # Update dependencies

# Code generation
gemvc create:crud User    # Create complete CRUD
gemvc create:service User # Create service only
gemvc create:controller User # Create controller only
```

## Troubleshooting

### Common Issues:

1. **Port Conflicts**: Ensure ports 80/9501, 3306, 8080 are available
2. **Database Connection**: Verify MySQL is running and credentials are correct
3. **Permissions**: Ensure proper file permissions for the `app/` directory
4. **Composer Issues**: Clear cache with `composer clear-cache`

### Getting Help:

- Check the [Documentation.md](../Documentation.md) for detailed information
- Review [GEMVC_INDEX.md](../GEMVC_INDEX.md) for component details
- Check the [AIAssist.jsonc](../AIAssist.jsonc) for AI assistant configuration

## Next Steps

- **Database Operations**: Learn about GEMVC's database features
- **Authentication**: Implement JWT-based authentication
- **WebSockets**: Add real-time capabilities
- **Deployment**: Learn about production deployment strategies

---

*You now have a fully configured GEMVC project with modern development tools, testing frameworks, and code quality tools!* 