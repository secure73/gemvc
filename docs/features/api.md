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

use App\Controller\UserController;
use Gemvc\Core\ApiService;
use Gemvc\Http\JsonResponse;

class User extends ApiService
{
    public function create(): JsonResponse
    {
        // Validate POST data using definePostSchema
        if(!$this->request->definePostSchema([
            'name' => 'string',
            'email' => 'email',
            'password' => 'string'
        ])) {
            return $this->request->returnResponse();
        }
        
        return (new UserController($this->request))->create();
    }

    public function read(): JsonResponse
    {
        // Validate GET parameters
        if(!$this->request->defineGetSchema(["id" => "int"])) {
            return $this->request->returnResponse();
        }
        
        $id = $this->request->intValueGet("id");
        if(!$id) {
            return $this->request->returnResponse();
        }
        
        $this->request->post['id'] = $id;
        return (new UserController($this->request))->read();
    }
}
```

### Creating a Controller

Create a new file at `app/controller/UserController.php`:

```php
<?php

namespace App\Controller;

use App\Model\UserModel;
use Gemvc\Core\Controller;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;

class UserController extends Controller
{
    public function create(): JsonResponse
    {
        $userModel = new UserModel();
        $userModel->request = $this->request;
        
        // Map POST data to model properties
        $this->request->mapPostToObject($userModel);
        $result = $userModel->insertSingleQuery();
        
        if ($result === null) {
            return Response::badRequest('Failed to create user');
        }
        
        return Response::success($result, 'User created successfully');
    }

    public function read(): JsonResponse
    {
        $userModel = new UserModel();
        $userModel->request = $this->request;
        
        $id = $this->request->post['id'];
        $user = $userModel->selectById($id);
        
        if ($user === null) {
            return Response::notFound('User not found');
        }
        
        return Response::success($user, 'User retrieved successfully');
    }
}
```

### Creating a Model

Create a new file at `app/model/UserModel.php`:

```php
<?php

namespace App\Model;

use App\Table\UserTable;

class UserModel extends UserTable
{
    public function getAll()
    {
        return $this->select()->run();
    }

    public function create(array $data)
    {
        // Map POST data to object properties
        $this->request->mapPostToObject($this);
        return $this->insertSingleQuery();
    }
}
```

## Advanced Features

### Request Validation

```php
public function update(): JsonResponse
{
    // Validate with current GEMVC validation schema
    if(!$this->request->definePostSchema([
        'id' => 'int',
        'name' => 'string',
        'email' => 'email'
    ])) {
        return $this->request->returnResponse();
    }

    // Validate string lengths
    if(!$this->request->validateStringPosts([
        'name' => '3|50',  // Min 3, max 50 characters
        'email' => '5|100' // Min 5, max 100 characters
    ])) {
        return $this->request->returnResponse();
    }

    return (new UserController($this->request))->update();
}
```

### Authentication

```php
public function getProfile(): JsonResponse
{
    // Check authentication using current GEMVC auth method
    if (!$this->request->auth()) {
        return $this->request->returnResponse();
    }

    // Get user ID from token
    $userId = $this->request->userId();
    $profile = $this->controller->getUserProfile($userId);
    
    return Response::success($profile);
}

public function adminOnly(): JsonResponse
{
    // Check for specific roles
    if (!$this->request->auth(['admin'])) {
        return $this->request->returnResponse();
    }
    
    return Response::success('Admin access granted');
}
```

### Error Handling

```php
public function delete(): JsonResponse
{
    try {
        // Validate required parameters
        if(!$this->request->definePostSchema([
            'id' => 'int'
        ])) {
            return $this->request->returnResponse();
        }
        
        $id = $this->request->intValuePost('id');
        
        if (!$this->controller->deleteUser($id)) {
            return Response::notFound('User not found');
        }

        return Response::success(null, 'User deleted successfully');
    } catch (\Exception $e) {
        return Response::internalError($e->getMessage());
    }
}
```

### Response Formatting

```php
// Success response
return Response::success($data, 'Operation successful');

// Error response
return Response::badRequest('Operation failed');

// Custom status codes (GEMVC specific)
return Response::updated($data, 'Resource updated'); // 209
return Response::deleted($data, 'Resource deleted'); // 210
```

### List Operations with Filtering and Sorting

```php
public function list(): JsonResponse
{
    // Define searchable fields and their types
    $this->request->findable([
        'name' => 'string',
        'email' => 'string'
    ]);

    // Define sortable fields
    $this->request->sortable([
        'id',
        'name',
        'created_at'
    ]);
    
    return (new UserController($this->request))->list();
}
```

## Best Practices

### 1. API Design
- Use RESTful conventions
- Implement proper HTTP methods
- Return appropriate status codes
- Version your APIs

### 2. Security
- Implement authentication using `$this->request->auth()`
- Validate all inputs using `definePostSchema()` and `defineGetSchema()`
- Sanitize outputs
- Use HTTPS

### 3. Performance
- Implement caching using RedisManager
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

## CLI Commands

Gemvc provides a powerful CLI tool for rapid development:

```bash
# Initialize a new project
vendor/bin/gemvc init

# Create individual components
vendor/bin/gemvc create:service ServiceName
vendor/bin/gemvc create:controller ServiceName
vendor/bin/gemvc create:model ServiceName
vendor/bin/gemvc create:table ServiceName

# Create service with specific components
vendor/bin/gemvc create:service ServiceName -c  # with controller
vendor/bin/gemvc create:service ServiceName -m  # with model
vendor/bin/gemvc create:service ServiceName -t  # with table
vendor/bin/gemvc create:service ServiceName -cmt  # with all components

# Create complete CRUD operations (recommended)
vendor/bin/gemvc create:crud ServiceName

# Database operations
vendor/bin/gemvc db:init
vendor/bin/gemvc db:migrate
vendor/bin/gemvc db:list
vendor/bin/gemvc db:describe TableName
vendor/bin/gemvc db:drop TableName
vendor/bin/gemvc db:unique TableName
vendor/bin/gemvc db:connect
```

The `create:crud` command is the most convenient way to generate a complete CRUD API - it creates the service, controller, model, and table files all at once. 