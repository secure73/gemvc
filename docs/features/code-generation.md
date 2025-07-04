# Code Generation

## Overview

GEMVC provides a comprehensive CLI tool for code generation, making it easy to create controllers, models, services, and database tables. The CLI tool is located in `src/CLI/` and provides various commands for rapid development.

## Installation

The CLI tool is automatically available when you install GEMVC:

```bash
composer require gemvc/library
```

## Available Commands

### Project Initialization

#### Initialize New Project
```bash
vendor/bin/gemvc init
```

This command:
- Creates the necessary directory structure
- Generates a sample `.env` file
- Sets up local command wrappers
- Creates basic configuration files

### Database Commands

#### Initialize Database
```bash
vendor/bin/gemvc db:init
```

Creates the initial database structure and tables.

#### List Tables
```bash
vendor/bin/gemvc db:list
```

Lists all tables in the database.

#### Describe Table
```bash
vendor/bin/gemvc db:describe <table_name>
```

Shows the structure of a specific table.

#### Create Table
```bash
vendor/bin/gemvc db:create:table <table_name>
```

Creates a new table with basic structure.

#### Drop Table
```bash
vendor/bin/gemvc db:drop <table_name>
```

Removes a table from the database.

#### Run Migrations
```bash
vendor/bin/gemvc db:migrate
```

Executes pending database migrations.

#### Add Unique Constraint
```bash
vendor/bin/gemvc db:unique <table_name> <column_name>
```

Adds a unique constraint to a column.

### Code Generation Commands

#### Create Controller
```bash
vendor/bin/gemvc create:controller <name>
```

Generates a new controller with standard CRUD operations.

**Options:**
- `--path=<path>`: Specify custom path for the controller
- `--namespace=<namespace>`: Custom namespace for the controller

**Example:**
```bash
vendor/bin/gemvc create:controller UserController
```

#### Create Model
```bash
vendor/bin/gemvc create:model <name>
```

Generates a new model with database interaction methods.

**Options:**
- `--table=<table_name>`: Specify the database table name
- `--path=<path>`: Specify custom path for the model

**Example:**
```bash
vendor/bin/gemvc create:model User
```

#### Create Service
```bash
vendor/bin/gemvc create:service <name>
```

Generates a new service class for business logic.

**Options:**
- `--path=<path>`: Specify custom path for the service
- `--namespace=<namespace>`: Custom namespace for the service

**Example:**
```bash
vendor/bin/gemvc create:service UserService
```

#### Create CRUD
```bash
vendor/bin/gemvc create:crud <name>
```

Generates a complete CRUD setup including controller, model, and service.

**Options:**
- `--table=<table_name>`: Specify the database table name
- `--path=<path>`: Specify custom path for generated files

**Example:**
```bash
vendor/bin/gemvc create:crud User
```

### Database Connection

#### Test Database Connection
```bash
vendor/bin/gemvc db:connect
```

Tests the database connection using the configured settings.

## Generated Code Structure

### Controller Template
```php
<?php

namespace App\Controllers;

use Gemvc\Core\Controller;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function index(): JsonResponse
    {
        // List all users
        return $this->success('Users retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        // Show specific user
        return $this->success('User retrieved successfully');
    }

    public function store(): JsonResponse
    {
        // Create new user
        return $this->success('User created successfully');
    }

    public function update(int $id): JsonResponse
    {
        // Update user
        return $this->success('User updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        // Delete user
        return $this->success('User deleted successfully');
    }
}
```

### Model Template
```php
<?php

namespace App\Models;

use Gemvc\Database\QueryBuilder;
use Gemvc\Traits\Model\CreateModelTrait;
use Gemvc\Traits\Model\UpdateTrait;
use Gemvc\Traits\Model\ListTrait;
use Gemvc\Traits\Model\IdTrait;

class User
{
    use CreateModelTrait, UpdateTrait, ListTrait, IdTrait;

    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];

    public function __construct()
    {
        // Initialize model
    }

    public function findByEmail(string $email): ?array
    {
        return QueryBuilder::select('*')
            ->from($this->table)
            ->whereEqual('email', $email)
            ->run($this->pdoQuery)
            ->fetch();
    }
}
```

### Service Template
```php
<?php

namespace App\Services;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class UserService extends ApiService
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function getUsers(): JsonResponse
    {
        // Business logic for getting users
        return $this->success('Users retrieved successfully');
    }

    public function createUser(): JsonResponse
    {
        // Business logic for creating user
        return $this->success('User created successfully');
    }
}
```

## Configuration

### CLI Configuration
The CLI tool uses the same configuration as your application:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_username
DB_PASSWORD=your_password

# Application Configuration
APP_ENV=development
APP_DEBUG=true
```

### Custom Templates
You can customize the generated code by modifying the templates in `src/CLI/templates/`:

- `controller.template` - Controller template
- `model.template` - Model template  
- `service.template` - Service template
- `table.template` - Database table template

## Best Practices

### 1. Naming Conventions
- Use PascalCase for class names
- Use camelCase for method names
- Use snake_case for database tables and columns
- Use descriptive names that reflect functionality

### 2. File Organization
- Keep controllers in `App/Controllers/`
- Keep models in `App/Models/`
- Keep services in `App/Services/`
- Use namespaces to organize code

### 3. Database Design
- Use meaningful table names
- Include timestamps (created_at, updated_at)
- Use proper data types
- Add indexes for performance

### 4. Code Generation
- Review generated code before using
- Customize templates for your needs
- Add validation and error handling
- Implement proper security measures

## Advanced Usage

### Custom Commands
You can extend the CLI tool by creating custom commands:

```php
<?php

namespace App\CLI\Commands;

use Gemvc\CLI\Command;

class CustomCommand extends Command
{
    protected string $name = 'custom:command';
    protected string $description = 'Custom command description';

    public function handle(): int
    {
        // Your command logic here
        return 0;
    }
}
```

### Batch Operations
```bash
# Generate multiple controllers
vendor/bin/gemvc create:controller UserController
vendor/bin/gemvc create:controller ProductController
vendor/bin/gemvc create:controller OrderController

# Generate complete CRUD for multiple entities
vendor/bin/gemvc create:crud User
vendor/bin/gemvc create:crud Product
vendor/bin/gemvc create:crud Order
```

## Troubleshooting

### Common Issues

#### Database Connection Failed
```bash
# Check your .env file
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_username
DB_PASSWORD=your_password

# Test connection
vendor/bin/gemvc db:connect
```

#### Permission Denied
```bash
# Make sure the CLI tool is executable
chmod +x vendor/bin/gemvc
```

#### Template Not Found
```bash
# Check if templates exist
ls src/CLI/templates/
```

## Next Steps

- [CLI Commands](../cli/commands.md)
- [Database Architecture](../core/database-architecture.md)
- [Getting Started](../getting-started/quick-start.md) 