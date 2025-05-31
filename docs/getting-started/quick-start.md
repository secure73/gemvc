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

## 1. Create Your First API Service

You can create a new API service using the CLI:

### Option 1: Service Only
```bash
vendor/bin/gemvc create:service User
```
- Generates: `app/api/User.php`

### Option 2: Service + Controller, Model, Table
```bash
vendor/bin/gemvc create:service User -cmt
```
- Generates:  
  - `app/api/User.php`  
  - `app/controller/UserController.php`  
  - `app/model/UserModel.php`  
  - `app/table/UserTable.php`

Flags:  
- `-c`: Controller  
- `-m`: Model  
- `-t`: Table

---

## 2. Create Individual Components

```bash
vendor/bin/gemvc create:controller User   # Controller
vendor/bin/gemvc create:model User        # Model
vendor/bin/gemvc create:table User        # Table
```

---

## 3. Create CRUD Operations

```bash
vendor/bin/gemvc create:crud User
```
Creates all files for full CRUD.

---

## 4. Database Management

**Common commands:**
```bash
vendor/bin/gemvc db:init                # Initialize database
vendor/bin/gemvc db:migrate UserTable   # Migrate/update table
vendor/bin/gemvc db:list                # List all tables
vendor/bin/gemvc db:drop                # Drop database
```

**Example Table Class:**
```php
namespace App\Table;
use Gemvc\Database\Table;

class UserTable extends Table {
    public int $id;
    public string $name;
    public string $email;
    public ?string $description;
    public string $created_at;
}
```

**Migrate:**
```bash
vendor/bin/gemvc db:migrate UserTable
```
- Adds new columns if you update the class.
- **Never removes columns automatically** (prevents data loss).

---

## 5. Test Your API

**Try your endpoint:**
```bash
curl http://localhost:9501/user/list
```
Or visit the [auto-generated API docs](http://localhost:9501/index/document).

**Sample response:**
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

---

## 6. Common Patterns

### Request Validation
```php
$this->validatePosts([
    'username' => 'string',
    'email' => 'email',
    '?bio' => 'string'  // Optional
]);
```

### Error Handling
```php
try {
    // ...
} catch (\Exception $e) {
    return (new JsonResponse())->error($e->getMessage());
}
```

### Authentication
```php
if(!$this->request->auth(['admin'])) {
    return $this->request->returnResponse();
}
```

---

## 7. CLI Commands Reference

- `vendor/bin/gemvc create:service ServiceName -cmt`
- `vendor/bin/gemvc create:controller ControllerName`
- `vendor/bin/gemvc create:model ModelName`
- `vendor/bin/gemvc create:table TableName`
- `vendor/bin/gemvc create:crud ResourceName`
- `vendor/bin/gemvc db:init`
- `vendor/bin/gemvc db:migrate TableClassName`
- `vendor/bin/gemvc db:tables`

---

## 8. Troubleshooting

- **Docker port in use?**  
  Stop other services using port 9501, 80, 3306, or 8080.
- **Missing PHP extensions?**  
  Use Docker or install required extensions: `pdo`, `openssl`, `gd`, `redis`, `openswoole` (optional).
- **Database connection issues?**  
  Check your `.env` file and Docker logs.

---

## 9. Next Steps

- [Core Features](../features/README.md)
- [Database Guide](../guides/database.md)
- [Authentication Guide](../guides/authentication.md)

---

*GEMVC: Made for developers who love clean, secure, and efficient code.* 