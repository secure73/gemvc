# 🚀 Quick Start Guide

Welcome to GEMVC! This guide will help you get your first API up and running in minutes.

---

## 0. Zero to Running in 60 Seconds

**1. Install GEMVC and initialize your project:**
```bash
composer require gemvc/library
vendor/bin/gemvc init
```

**2. Start the development environment (Docker recommended):**
```bash
docker-compose up --build
```
> **Tip:** GEMVC comes with a ready-to-use Docker setup (PHP, MySQL, Redis, PHPMyAdmin). No manual config needed!

---

## 1. Create Your First API

Generate a complete CRUD API for a resource:

```bash
vendor/bin/gemvc create:crud Product
```

This creates:
- `app/api/Product.php` - API endpoints
- `app/controller/ProductController.php` - Business logic
- `app/model/ProductModel.php` - Data model
- `app/table/ProductTable.php` - Database schema

## 2. Set Up Database

Initialize and migrate your database:

```bash
vendor/bin/gemvc db:init
vendor/bin/gemvc db:migrate ProductTable
vendor/bin/gemvc db:describe ptoducts

```

---

## 3. Test Your API

**Try your endpoint:**
```bash
curl http://localhost:9501/api/user/list
```
Or visit the [auto-generated API docs](http://localhost:9501/index/document).

**Sample response:**
```json
{
    "response_code": 200,
    "message": "OK",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2024-01-01 12:00:00"
        }
    ],
    "count": 1,
    "service_message": "list of users fetched successfully"
}
```

---

## 4. Common Patterns

### Request Validation
```php
// In your API service
if(!$this->request->definePostSchema([
    'name' => 'string',
    'email' => 'email',
    '?description' => 'string'  // Optional field
])) {
    return $this->request->returnResponse();
}

// Validate string lengths
if(!$this->request->validateStringPosts([
    'name' => '3|50',      // Min 3, max 50 characters
    'email' => '5|100'     // Min 5, max 100 characters
])) {
    return $this->request->returnResponse();
}
```

### Error Handling
```php
try {
    // Your code here
} catch (\Exception $e) {
    return Response::internalError($e->getMessage());
}
```

### Authentication
```php
// Simple authentication check
if (!$this->request->auth()) {
    return $this->request->returnResponse();
}

// Role-based authorization
if (!$this->request->auth(['admin'])) {
    return $this->request->returnResponse();
}
```

### Database Operations
```php
// In your Model class (extends Table)
public function getAllUsers() {
    return $this->select('id, name, email')
                ->where('is_active', true)
                ->orderBy('created_at', false) // DESC
                ->run();
}

public function createUser() {
    $this->request->mapPostToObject($this);
    return $this->insertSingleQuery();
}
```

### List Operations with Filtering
```php
public function list(): JsonResponse
{
    // Define searchable fields
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

---

## 5. CLI Commands Reference

### Project Management
- `vendor/bin/gemvc init` - Initialize new project

### Code Generation
- `vendor/bin/gemvc create:service ServiceName`
- `vendor/bin/gemvc create:controller ControllerName`
- `vendor/bin/gemvc create:model ModelName`
- `vendor/bin/gemvc create:table TableName`

### Database Management
- `vendor/bin/gemvc db:connect`
- `vendor/bin/gemvc db:describe TableClassName`
- `vendor/bin/gemvc db:list`
- `vendor/bin/gemvc db:migrate TableClassName`
- `vendor/bin/gemvc db:unique TableClassName`

### Help & Information
- `vendor/bin/gemvc --help`
- `vendor/bin/gemvc --version`

---

## 6. Troubleshooting

- **Docker port in use?**  
  Stop other services using port 9501, 80, 3306, or 8080.
- **Missing PHP extensions?**  
  Use Docker or install required extensions: `pdo`, `openssl`, `gd`, `redis`, `openswoole` (optional).
- **Database connection issues?**  
  Check your `.env` file and Docker logs.
- **CLI command not found?**  
  Make sure you're in the project root directory and run `composer install`.

---

## 7. Next Steps

- [Core Features](../features/api.md)
- [Database Guide](../core/database-architecture.md)
- [Authentication Guide](../guides/authentication.md)

---

*GEMVC: Made for developers who love clean, secure, and efficient code.* 