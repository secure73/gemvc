# Quick Start Guide

This guide will help you create your first API endpoint with GEMVC.

## 1. Create Your First API Service

You can create a new API service using the CLI command in two ways:

### Option 1: Create Service Only
```bash
vendor/bin/gemvc create:service User
```
This command will generate:
- Service file (`app/api/User.php`)

### Option 2: Create Service with All Components
```bash
vendor/bin/gemvc create:service User -cmt
```
This command will generate:
- Service file (`app/api/User.php`)
- Controller file (`app/controller/UserController.php`)
- Model file (`app/model/UserModel.php`)
- Table file (`app/table/UserTable.php`)

The flags mean:
- `-c`: Create Controller
- `-m`: Create Model
- `-t`: Create Table

## 2. Create Individual Components

If you prefer to create components separately, you can use these commands:

### Create a Controller
```bash
vendor/bin/gemvc create:controller User
```
This command will generate:
- Controller file (`app/controller/UserController.php`)

### Create a Model
```bash
vendor/bin/gemvc create:model User
```
This command will generate:
- Model file (`app/model/UserModel.php`)

### Create a Table
```bash
vendor/bin/gemvc create:table User
```
This command will generate:
- Table file (`app/table/UserTable.php`)

## 3. Create CRUD Operations

To create all CRUD operations for a resource:

```bash
vendor/bin/gemvc create:crud User
```

This will create all necessary files for CRUD operations.

## 4. Database Management

GEMVC provides several commands for database management:

### Create Database
```bash
vendor/bin/gemvc db:init
```
Creates the database based on your configuration.

### Create or Update Tables
```bash
vendor/bin/gemvc db:migrate UserTable
```
Creates or updates a specific table based on its table class.

### List Database Tables
```bash
vendor/bin/gemvc db:tables
```
Shows a list of all tables in your database.

## 5. Test Your API

Your API endpoint is now ready at:
```
GET /user/list
```

Expected response:
```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "username": "john_doe",
            "email": "john@example.com",
            "is_active": true
        }
    ]
}
```

## CLI Commands Reference

GEMVC provides several CLI commands to help you generate code and manage your database:

### Code Generation
1. **Create Service with All Components**
   ```bash
   vendor/bin/gemvc create:service ServiceName -cmt
   ```
   Generates service, controller, model, and table files.

2. **Create Controller**
   ```bash
   vendor/bin/gemvc create:controller ControllerName
   ```
   Generates controller file.

3. **Create Model**
   ```bash
   vendor/bin/gemvc create:model ModelName
   ```
   Generates model file.

4. **Create Table**
   ```bash
   vendor/bin/gemvc create:table TableName
   ```
   Generates table class file.

5. **Create CRUD**
   ```bash
   vendor/bin/gemvc create:crud ResourceName
   ```
   Generates all files needed for CRUD operations.

### Database Management
1. **Create Database**
   ```bash
   vendor/bin/gemvc db:init
   ```
   Creates the database based on configuration.

2. **Migrate Table**
   ```bash
   vendor/bin/gemvc db:migrate TableClassName
   ```
   Creates or updates a specific table.

3. **List Tables**
   ```bash
   vendor/bin/gemvc db:tables
   ```
   Shows all tables in the database.

## Next Steps

- [Core Features](../features/README.md)
- [Database Guide](../guides/database.md)
- [Authentication Guide](../guides/authentication.md)

## Common Patterns

### 1. Request Validation

```php
public function create(): JsonResponse
{
    // Validate input
    $this->validatePosts([
        'username' => 'string',
        'email' => 'email',
        '?bio' => 'string'  // Optional field
    ]);
    
    // Process request
    return (new UserController($this->request))->create();
}
```

### 2. Error Handling

```php
public function update(): JsonResponse
{
    try {
        // Your code here
    } catch (\Exception $e) {
        return (new JsonResponse())->error($e->getMessage());
    }
}
```

### 3. Authentication

```php
// Simple authentication
if(!$this->request->auth()) {
    return $this->request->returnResponse();
}

// Role-based authentication
if(!$this->request->auth(['admin', 'manager'])) {
    return $this->request->returnResponse();
}
```

## Best Practices

1. **Service Layer**
   - Handle request validation
   - Manage authentication
   - Route to appropriate controller

2. **Controller Layer**
   - Process business logic
   - Handle data flow
   - Return appropriate responses

3. **Model Layer**
   - Extend from Table
   - Add business logic
   - Handle data processing

4. **Table Layer**
   - Define database structure
   - Handle database operations
   - Manage data persistence 