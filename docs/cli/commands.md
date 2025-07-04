# GEMVC CLI Documentation

## Overview

GEMVC provides a powerful command-line interface for project management, code generation, and maintenance tasks. This document covers both the CLI architecture and available commands.

## Core Components

### 1. Command (`src/CLI/Command.php`)
- Base class for all commands
- Command registration
- Argument parsing
- Output formatting

### 2. BaseGenerator (`src/CLI/commands/BaseGenerator.php`)
- Base class for code generators
- Template processing
- File generation
- Code formatting

### 3. Command Registry
- Command discovery
- Command registration
- Command execution
- Help generation

## Available Commands

### Project Management
```bash
# Initialize project
vendor/bin/gemvc init

# Test installation
vendor/bin/gemvc test:install
```

### Code Generation
```bash
# Generate service
vendor/bin/gemvc create:service User

# Generate controller
vendor/bin/gemvc create:controller User

# Generate model
vendor/bin/gemvc create:model User

# Generate table
vendor/bin/gemvc create:table User
```

### Database Management

#### Test Database Connection
```bash
vendor/bin/gemvc db:connect
```
- Tests the database connection using your `.env` configuration
- Shows connection status and any errors
- Safe to run multiple times

#### Describe Table Structure
```bash
vendor/bin/gemvc db:describe TableClassName
```
- Shows detailed table structure
- Displays:
  - Column names and types
  - Primary keys
  - Indexes
  - Foreign keys
  - Constraints
- Example:
  ```bash
  vendor/bin/gemvc db:describe UserTable
  ```

#### Migrate Table
```bash
vendor/bin/gemvc db:migrate TableClassName [--force]
```
- Creates or updates a table based on its PHP class definition
- Actions:
  - Creates new table if it doesn't exist
  - Adds new columns for new properties
  - Updates column types if changed
  - Updates nullable status
  - Manages indexes
  - Removes columns only if `--force` is used
- Example:
  ```bash
  # Safe update (won't remove columns)
  vendor/bin/gemvc db:migrate UserTable

  # Force update (will remove columns not in class)
  vendor/bin/gemvc db:migrate UserTable --force
  ```

#### List Tables
```bash
vendor/bin/gemvc db:list
```
- Shows all tables in the database
- Displays:
  - Table names
  - Column names and types
  - Primary keys
  - Indexes
- Example output:
  ```
  Tables in database 'gemvc_db':
  - users
    - id (int, primary key)
    - name (varchar)
    - email (varchar, unique)
  ```

#### Add Unique Constraints
```bash
vendor/bin/gemvc db:unique TableClassName
```
- Adds unique constraints to table columns
- Based on the table class definition
- Example:
  ```bash
  vendor/bin/gemvc db:unique UserTable
  ```

### Help & Information
```bash
# Show help
vendor/bin/gemvc --help

# Show version
vendor/bin/gemvc --version
```

## Command Structure

### Basic Command
```php
class MyCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
        $this->setName('my:command');
        $this->setDescription('My custom command');
    }

    public function execute(): void
    {
        // Command logic here
        $this->output->writeln('Command executed');
    }
}
```

### Command with Arguments
```php
class CreateUserCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
        $this->setName('create:user');
        $this->setDescription('Create a new user');
        
        // Add arguments
        $this->addArgument('username', 'User username');
        $this->addArgument('email', 'User email');
        
        // Add options
        $this->addOption('admin', 'a', 'Create admin user');
    }

    public function execute(): void
    {
        $username = $this->getArgument('username');
        $email = $this->getArgument('email');
        $isAdmin = $this->getOption('admin');

        // Command logic here
        $this->output->writeln("Creating user: $username");
    }
}
```

## Output Formatting

### Basic Output
```php
// Write line
$this->output->writeln('Message');

// Write without newline
$this->output->write('Message');

// Write with style
$this->output->writeln('<info>Success</info>');
$this->output->writeln('<error>Error</error>');
$this->output->writeln('<comment>Comment</comment>');
```

### Progress Bar
```php
$progress = $this->output->createProgressBar(100);
```

## Command Examples

### Project Initialization
```bash
# Initialize a new GEMVC project
vendor/bin/gemvc init

# This will:
# - Create directory structure (app/api, app/controller, app/model, app/table)
# - Copy templates and startup files
# - Create .env file
# - Set up command wrappers
# - Choose between Apache or OpenSwoole template
```

### Database Operations
```bash
# Test database connection
vendor/bin/gemvc db:connect

# Create/update a table
vendor/bin/gemvc db:migrate UserTable

# List all tables
vendor/bin/gemvc db:list

# Describe table structure
vendor/bin/gemvc db:describe UserTable

# Add unique constraints
vendor/bin/gemvc db:unique UserTable
```

### Code Generation
```bash
# Generate a complete service
vendor/bin/gemvc create:service User

# Generate individual components
vendor/bin/gemvc create:controller User
vendor/bin/gemvc create:model User
vendor/bin/gemvc create:table User
```

## Troubleshooting

### Common Issues

1. **Command not found**
   - Ensure you're in the project root directory
   - Run `composer install` to install dependencies
   - Check if `vendor/bin/gemvc` exists

2. **Database connection errors**
   - Verify your `.env` file configuration
   - Check database server is running
   - Ensure database credentials are correct

3. **Permission errors**
   - Check file and directory permissions
   - Ensure you have write access to the project directory

4. **Template errors**
   - Verify template files exist in `vendor/gemvc/library/src/startup/`
   - Check template directory permissions

## Next Steps

- [CLI Components](cli-components.md)
- [Quick Start Guide](../getting-started/quick-start.md)
- [API Features](../features/api.md)

# CLI Commands Reference

Gemvc provides a comprehensive CLI tool for rapid development and project management.

## Project Management

### Initialize Project
```bash
vendor/bin/gemvc init
```
Creates a new Gemvc project with the selected template (Apache or Swoole).

## Component Generation

### Create Complete CRUD (Recommended)
```bash
vendor/bin/gemvc create:crud ServiceName
```
Creates all CRUD components at once:
- Service (API endpoints)
- Controller (business logic)
- Model (data model)
- Table (database schema)

### Create Individual Components
```bash
# Create service only
vendor/bin/gemvc create:service ServiceName

# Create service with specific components
vendor/bin/gemvc create:service ServiceName -c  # with controller
vendor/bin/gemvc create:service ServiceName -m  # with model
vendor/bin/gemvc create:service ServiceName -t  # with table
vendor/bin/gemvc create:service ServiceName -cmt  # with all components

# Create individual components
vendor/bin/gemvc create:controller ServiceName
vendor/bin/gemvc create:model ServiceName
vendor/bin/gemvc create:table ServiceName
```

## Database Management

### Database Operations
```bash
vendor/bin/gemvc db:init          # Initialize database connection
vendor/bin/gemvc db:migrate       # Run all table migrations
vendor/bin/gemvc db:list          # List all tables
vendor/bin/gemvc db:describe TableName  # Describe table structure
vendor/bin/gemvc db:drop TableName      # Drop table
vendor/bin/gemvc db:unique TableName    # Add unique constraints
vendor/bin/gemvc db:connect       # Test database connection
```

## Examples

### Create a Product API
```bash
# Generate complete CRUD
vendor/bin/gemvc create:crud Product

# Set up database
vendor/bin/gemvc db:init
vendor/bin/gemvc db:migrate
```

### Create a User Management System
```bash
# Generate complete CRUD
vendor/bin/gemvc create:crud User

# Set up database
vendor/bin/gemvc db:init
vendor/bin/gemvc db:migrate
```

## Command Flags

### Service Creation Flags
- `-c`: Include controller
- `-m`: Include model  
- `-t`: Include table
- `-cmt`: Include all components (same as create:crud)

## File Structure Generated

When using `create:crud ServiceName`, the following structure is created:

```
app/
├── api/
│   └── ServiceName.php          # API endpoints
├── controller/
│   └── ServiceNameController.php # Business logic
├── model/
│   └── ServiceNameModel.php     # Data model
└── table/
    └── ServiceNameTable.php     # Database schema
```

## Best Practices

1. **Use `create:crud`** for most cases - it's the fastest way to get a complete API
2. **Use individual commands** when you need specific components or want to customize the generation
3. **Always run `db:migrate`** after creating table classes to sync with your database
4. **Use `db:describe`** to verify your table structure matches your expectations 