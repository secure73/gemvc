# API Features

## Overview

GEMVC provides a robust API system with built-in support for RESTful endpoints, request validation, response formatting, and authentication.

## Core Features

### 1. API Service Layer
- Request handling
- Response formatting
- Input validation
- Error handling
- Authentication

### 2. Controller Layer
- Route handling
- Business logic
- Model interaction
- Response generation

### 3. Model Layer
- Data validation
- Business rules
- Database interaction
- Relationship handling

## Basic Usage

### Creating an API Service

Create a new file at `app/api/User.php`:

```php
<?php

namespace App\Api;

use Gemvc\Core\ApiService;

class User extends ApiService
{
    public function getUsers()
    {
        $users = $this->controller->getUsers();
        return $this->success($users);
    }

    public function createUser()
    {
        $data = $this->request->getJson();
        
        // Validate input
        if (!$this->validate($data, [
            'name' => 'required|string',
            'email' => 'required|email'
        ])) {
            return $this->error('Invalid input');
        }

        $user = $this->controller->createUser($data);
        return $this->success($user);
    }
}
```

### Creating a Controller

Create a new file at `app/controller/UserController.php`:

```php
<?php

namespace App\Controller;

use App\Model\UserModel;

class UserController
{
    private UserModel $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function getUsers()
    {
        return $this->model->getAll();
    }

    public function createUser(array $data)
    {
        return $this->model->create($data);
    }
}
```

### Creating a Model

Create a new file at `app/model/UserModel.php`:

```php
<?php

namespace App\Model;

use App\Table\UserTable;

class UserModel
{
    private UserTable $table;

    public function __construct()
    {
        $this->table = new UserTable();
    }

    public function getAll()
    {
        return $this->table->select()->fetchAll();
    }

    public function create(array $data)
    {
        return $this->table->insert($data);
    }
}
```

## Advanced Features

### Request Validation

```php
public function updateUser()
{
    $data = $this->request->getJson();
    
    // Validate with custom rules
    if (!$this->validate($data, [
        'id' => 'required|integer',
        'name' => 'required|string|min:3',
        'email' => 'required|email|unique:users',
        'age' => 'integer|min:18'
    ])) {
        return $this->error('Validation failed', $this->getErrors());
    }

    $user = $this->controller->updateUser($data);
    return $this->success($user);
}
```

### Authentication

```php
public function getProfile()
{
    // Check authentication
    if (!$this->request->auth()) {
        return $this->error('Unauthorized', null, 401);
    }

    // Get user ID from token
    $userId = $this->request->userId();
    $profile = $this->controller->getUserProfile($userId);
    
    return $this->success($profile);
}
```

### Error Handling

```php
public function deleteUser()
{
    try {
        $id = $this->request->getParam('id');
        
        if (!$this->controller->deleteUser($id)) {
            return $this->error('User not found', null, 404);
        }

        return $this->success(null, 'User deleted successfully');
    } catch (\Exception $e) {
        return $this->error('Server error', $e->getMessage(), 500);
    }
}
```

### Response Formatting

```php
// Success response
return $this->success($data, 'Operation successful');

// Error response
return $this->error('Operation failed', $errors, 400);

// Custom response
return $this->response([
    'status' => 'success',
    'data' => $data,
    'meta' => [
        'page' => 1,
        'total' => 100
    ]
]);
```

## Best Practices

### 1. API Design
- Use RESTful conventions
- Implement proper HTTP methods
- Return appropriate status codes
- Version your APIs

### 2. Security
- Implement authentication
- Validate all inputs
- Sanitize outputs
- Use HTTPS

### 3. Performance
- Implement caching
- Optimize database queries
- Use pagination
- Compress responses

### 4. Error Handling
- Use proper error codes
- Provide clear error messages
- Log errors appropriately
- Handle exceptions

### 5. Documentation
- Document all endpoints
- Provide usage examples
- Include error responses
- Keep documentation updated

## Next Steps

- [Authentication Guide](../guides/authentication.md)
- [Database Guide](../guides/database.md)
- [Security Guide](../guides/security.md) 