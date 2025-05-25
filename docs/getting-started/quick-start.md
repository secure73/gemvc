# Quick Start Guide

This guide will help you create your first API endpoint with GEMVC.

## 1. Create Your First API Service

You can create a new API service using the CLI command:

```bash
vendor/bin/gemvc create:service User -cmt
```

This command will generate:
- Service file (`app/api/User.php`)
- Controller file (`app/controller/UserController.php`)
- Model file (`app/model/UserModel.php`)
- Table file (`app/table/UserTable.php`)

Alternatively, you can create files manually:

Create a new file in `app/api/User.php`:

```php
<?php
namespace App\Api;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class User extends ApiService {
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    
    public function getUsers(): JsonResponse {
        // Authentication check
        if(!$this->request->auth(['admin'])) {
            return $this->request->returnResponse();
        }
        
        // Call controller
        return (new UserController($this->request))->list();
    }
}
```

## 2. Create a Controller

You can create a controller using the CLI command:

```bash
vendor/bin/gemvc create:controller User -mt
```

This command will generate:
- Controller file (`app/controller/UserController.php`)
- Model file (`app/model/UserModel.php`)
- Table file (`app/table/UserTable.php`)

Alternatively, you can create the controller manually:

Create a new file in `app/controller/UserController.php`:

```php
<?php
namespace App\Controller;

use App\Model\UserModel;
use Gemvc\Core\Controller;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function list(): JsonResponse
    {
        $model = new UserModel();
        return $model->list();
    }
}
```

## 3. Create a Model

You can create a model using the CLI command:

```bash
vendor/bin/gemvc create:model User -t
```

This command will generate:
- Model file (`app/model/UserModel.php`)
- Table file (`app/table/UserTable.php`)

Alternatively, you can create the model manually:

Create a new file in `app/model/UserModel.php`:

```php
<?php
namespace App\Model;

use App\Table\UserTable;
use Gemvc\Core\Model;
use Gemvc\Http\JsonResponse;

class UserModel extends Model
{
    public function list(): JsonResponse
    {
        $table = new UserTable();
        $users = $table->select()
            ->where('is_active', true)
            ->run();
            
        return (new JsonResponse())->success($users);
    }
}
```

## 4. Create a Table

You can create a table using the CLI command:

```bash
vendor/bin/gemvc create:table User
```

This command will generate:
- Table file (`app/table/UserTable.php`)

Alternatively, you can create the table manually:

Create a new file in `app/table/UserTable.php`:

```php
<?php
namespace App\Table;

use Gemvc\Database\Table;

class UserTable extends Table
{
    public int $id;
    public string $username;
    public string $email;
    public bool $is_active = true;
    
    protected array $_type_map = [
        'id' => 'int',
        'is_active' => 'bool'
    ];
    
    public function __construct()
    {
        parent::__construct('users');
    }
}
```

## 5. Generate the Database Table

Run the table generator:

```bash
vendor/bin/gemvc create:table User
```

## 6. Test Your API

Your API endpoint is now ready at:
```
GET /user/getUsers
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

GEMVC provides several CLI commands to help you generate code:

1. **Create Service with All Components**
   ```bash
   vendor/bin/gemvc create:service ServiceName -cmt
   ```
   Generates service, controller, model, and table files.

2. **Create Controller with Model and Table**
   ```bash
   vendor/bin/gemvc create:controller ControllerName -mt
   ```
   Generates controller, model, and table files.

3. **Create Model with Table**
   ```bash
   vendor/bin/gemvc create:model ModelName -t
   ```
   Generates model and table files.

4. **Create Table Only**
   ```bash
   vendor/bin/gemvc create:table TableName
   ```
   Generates only the table file.

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