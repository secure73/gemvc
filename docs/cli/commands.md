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

# Setup server
vendor/bin/gemvc setup apache
vendor/bin/gemvc setup swoole

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

# Generate CRUD
vendor/bin/gemvc create:crud User
```

### Database Management

#### Initialize Database
```bash
vendor/bin/gemvc db:init
```
- Creates the database as defined in your `.env` configuration
- Safe to run multiple times
- Creates database if it doesn't exist
- No data loss if database already exists

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

#### Drop Table
```bash
vendor/bin/gemvc db:drop TableName --force
```
- Deletes a table and all its data
- Requires `--force` flag to prevent accidental deletion
- Example:
  ```bash
  vendor/bin/gemvc db:drop users --force
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

for ($i = 0; $i < 100; $i++) {
    // Do something
    $progress->advance();
}

$progress->finish();
```

### Table Output
```php
$table = $this->output->createTable();
$table->setHeaders(['Name', 'Email']);

$table->addRow(['John Doe', 'john@example.com']);
$table->addRow(['Jane Doe', 'jane@example.com']);

$table->render();
```

## Error Handling

### Command Errors
```php
try {
    // Command logic
} catch (\Exception $e) {
    $this->output->writeln('<error>' . $e->getMessage() . '</error>');
    exit(1);
}
```

### Validation Errors
```php
if (!$this->validateArguments()) {
    $this->output->writeln('<error>Invalid arguments</error>');
    exit(1);
}
```

## Best Practices

### 1. Command Structure
- Use clear command names
- Provide helpful descriptions
- Document arguments and options
- Handle errors gracefully

### 2. Code Generation
- Use templates for consistency
- Validate input before generation
- Handle file conflicts
- Provide helpful feedback

### 3. Output Formatting
- Use appropriate styles
- Show progress for long operations
- Format tables for readability
- Handle errors clearly

### 4. Error Handling
- Validate input early
- Handle exceptions properly
- Provide helpful error messages
- Use appropriate exit codes

### 5. Testing
- Test command logic
- Test argument parsing
- Test error handling
- Test output formatting

## Safety Notes
- Commands that modify data or structure require `--force` flag
- Always back up your data before using destructive commands
- Use `--help` to see command-specific options
- `db:migrate` will never remove columns unless `--force` is used
- `db:drop` requires `--force` to prevent accidental data loss
- `db:migrate` will never change a column from NULL to NOT NULL unless `--enforce-not-null` is used
- If you use `--enforce-not-null` and there are existing NULLs, you must provide `--default` or handle the data manually

## Next Steps

- [Request Lifecycle](../core/request-lifecycle.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 