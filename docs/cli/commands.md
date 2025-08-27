# CLI Commands Reference

## Overview

GEMVC provides a comprehensive command-line interface for project management, code generation, and database operations. All commands are accessible through the `gemvc` binary.

## Command Structure

```bash
vendor/bin/gemvc [command] [options] [arguments]
```

## Project Management

### `init` - Initialize New Project

Creates a new GEMVC project with complete setup including optional development tools.

```bash
vendor/bin/gemvc init
```

**What it does:**
1. Creates project directory structure (`app/`, `bin/`, `templates/`)
2. Copies server templates (Apache or OpenSwoole)
3. Sets up environment configuration
4. Creates local command wrappers
5. Offers PHPStan installation for code quality
6. Offers testing framework choice (PHPUnit or Pest)

**Options:**
- `--non-interactive` or `-n`: Skip all prompts, use defaults

**Example Output:**
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

**Features Added:**
- **PHPStan Integration**: Static analysis tool for code quality
- **Testing Framework Choice**: PHPUnit (traditional) or Pest (modern)
- **Automatic Configuration**: Pre-configured files and composer scripts
- **Non-Interactive Mode**: Perfect for CI/CD and automation

## Code Generation

### `create:service` - Create Service

Creates a new API service with optional components.

```bash
vendor/bin/gemvc create:service ServiceName [options]
```

**Options:**
- `-c`: Include controller
- `-m`: Include model
- `-t`: Include table

**Examples:**
```bash
# Create service only
gemvc create:service User

# Create service with all components
gemvc create:service User -cmt

# Create service with specific components
gemvc create:service Product -cm
```

### `create:controller` - Create Controller

Creates a new controller for business logic.

```bash
vendor/bin/gemvc create:controller ControllerName
```

**Example:**
```bash
gemvc create:controller UserController
```

### `create:model` - Create Model

Creates a new model for data processing.

```bash
vendor/bin/gemvc create:model ModelName
```

**Example:**
```bash
gemvc create:model UserModel
```

### `create:table` - Create Table

Creates a new table class for database operations.

```bash
vendor/bin/gemvc create:table TableName
```

**Example:**
```bash
gemvc create:table UserTable
```

### `create:crud` - Create Complete CRUD

Creates a complete CRUD operation set for a resource.

```bash
vendor/bin/gemvc create:crud ResourceName
```

**What it creates:**
- Service class
- Controller class
- Model class
- Table class

**Example:**
```bash
gemvc create:crud User
```

## Database Management

### `db:init` - Initialize Database

Initializes the database based on configuration.

```bash
vendor/bin/gemvc db:init
```

### `db:connect` - Test Database Connection

Tests the database connection.

```bash
vendor/bin/gemvc db:connect
```

### `db:migrate` - Create/Update Table

Creates or updates a specific table.

```bash
vendor/bin/gemvc db:migrate TableClassName
```

**Example:**
```bash
gemvc db:migrate UserTable
```

### `db:list` - List Tables

Shows all tables in the database.

```bash
vendor/bin/gemvc db:list
```

### `db:describe` - Describe Table

Shows the structure of a specific table.

```bash
vendor/bin/gemvc db:describe TableName
```

**Example:**
```bash
gemvc db:describe users
```

### `db:drop` - Drop Table

Drops a specific table.

```bash
vendor/bin/gemvc db:drop TableName
```

**Example:**
```bash
gemvc db:drop users
```

### `db:unique` - Check Unique Constraints

Checks unique constraints for a table.

```bash
vendor/bin/gemvc db:unique TableClassName
```

## Development Tools

### PHPStan Integration

If you chose to install PHPStan during `init`, you can use:

```bash
# Run static analysis
composer phpstan

# Run with JSON output (for CI/CD)
composer phpstan:check

# Direct PHPStan command
./vendor/bin/phpstan analyse

# Custom analysis level
./vendor/bin/phpstan analyse --level=5
```

### Testing Framework

Depending on your choice during `init`:

#### PHPUnit
```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Direct PHPUnit command
./vendor/bin/phpunit
```

#### Pest
```bash
# Run all tests
composer test

# Run in parallel
composer test:parallel

# Direct Pest command
./vendor/bin/pest
```

## Global Options

### `--help` or `-h`

Shows help information for a command.

```bash
gemvc --help
gemvc init --help
```

### `--version` or `-v`

Shows the GEMVC version.

```bash
gemvc --version
```

### `--non-interactive` or `-n`

Runs commands in non-interactive mode, using defaults.

```bash
gemvc init --non-interactive
```

## Command Categories

### Project Management
- `init` - Initialize new GEMVC project with server configuration (Apache/Swoole), optional PHPStan installation, and testing framework choice (PHPUnit/Pest)

### Code Generation
- `create:service` - Create a new service with optional components (-c: controller, -m: model, -t: table)
- `create:controller` - Create a new controller for handling business logic
- `create:model` - Create a new model for data processing and business rules
- `create:table` - Create a new table class for database operations
- `create:crud` - Create complete CRUD operations for a resource (service, controller, model, table)

### Database
- `db:init` - Initialize database based on configuration
- `db:migrate` - Create or update a specific table (db:migrate TableClassName)
- `db:list` - Show list of all tables in the database
- `db:drop` - Drop a specific table (db:drop TableName)

## Examples

### Complete Project Setup

```bash
# 1. Install GEMVC
composer require gemvc/library

# 2. Initialize project
vendor/bin/gemvc init

# 3. Install dependencies
composer update

# 4. Create CRUD for User
vendor/bin/gemvc create:crud User

# 5. Initialize database
vendor/bin/gemvc db:init

# 6. Migrate User table
vendor/bin/gemvc db:migrate UserTable

# 7. Run tests
composer test

# 8. Run code quality analysis
composer phpstan
```

### Development Workflow

```bash
# Daily development cycle
composer test          # Run tests
composer phpstan       # Static analysis
composer update        # Update dependencies

# Code generation
gemvc create:crud Product    # Create new resource
gemvc create:service Order   # Create service only
gemvc create:controller Cart # Create controller only

# Database operations
gemvc db:migrate ProductTable
gemvc db:list
gemvc db:describe products
```

## Troubleshooting

### Common Issues

1. **Command not found**: Ensure you're in the project root and run `composer install`
2. **Permission denied**: Check file permissions for the `bin/` directory
3. **Database connection**: Verify your `.env` configuration
4. **PHPStan not working**: Ensure it was installed during `init` or run `composer require --dev phpstan/phpstan`

### Getting Help

```bash
# Show all available commands
gemvc --help

# Show help for specific command
gemvc init --help
gemvc create:crud --help

# Show version
gemvc --version
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: GEMVC Tests
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

---

*The GEMVC CLI provides everything you need to manage your project from initialization to deployment, with modern development tools and best practices built-in.* 