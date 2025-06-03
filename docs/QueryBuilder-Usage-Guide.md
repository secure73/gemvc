# Enhanced QueryBuilder Usage Guide

This guide demonstrates how to use the enhanced QueryBuilder system with practical examples showcasing all the modern features, safety enhancements, and best practices.

## 🚀 Getting Started

```php
<?php
use Gemvc\Database\QueryBuilder;

// Create a new QueryBuilder instance
$queryBuilder = new QueryBuilder();
```

## 📖 Table of Contents

1. [SELECT Queries](#select-queries)
2. [INSERT Queries](#insert-queries)
3. [UPDATE Queries](#update-queries)
4. [DELETE Queries](#delete-queries)
5. [Enhanced WHERE Conditions](#enhanced-where-conditions)
6. [Pagination & Limiting](#pagination--limiting)
7. [Error Handling](#error-handling)
8. [Transactions](#transactions)
9. [Integration with Table Classes](#integration-with-table-classes)
10. [Best Practices](#best-practices)

---

## SELECT Queries

### Basic SELECT
```php
$users = $queryBuilder->select('id', 'name', 'email')
    ->from('users')
    ->run();

if ($users === null) {
    echo "Error: " . $queryBuilder->getError();
} else {
    foreach ($users as $user) {
        echo "User: " . $user['name'] . "\n";
    }
}
```

### SELECT with Aliases and JOINs
```php
$userPosts = $queryBuilder->select('u.name', 'p.title', 'p.created_at')
    ->from('users', 'u')
    ->leftJoin('posts p ON u.id = p.user_id')
    ->whereEqual('u.status', 'active')
    ->orderBy('p.created_at', true) // DESC
    ->run();
```

### SELECT with Complex Conditions
```php
$products = $queryBuilder->select('*')
    ->from('products')
    ->whereEqual('category', 'electronics')
    ->whereBetween('price', 100, 500)
    ->whereIn('brand', ['apple', 'samsung', 'google'])
    ->whereNotNull('description')
    ->orderBy('price')
    ->limit(20)
    ->run();
```

### Get JSON Results
```php
$jsonData = $queryBuilder->select('id', 'name', 'email')
    ->from('users')
    ->whereEqual('status', 'active')
    ->json();

if ($jsonData === null) {
    echo "Error: " . $queryBuilder->getError();
} else {
    echo $jsonData; // JSON string ready for API response
}
```

---

## INSERT Queries

### Basic INSERT
```php
$insertId = $queryBuilder->insert('users')
    ->columns('name', 'email', 'age', 'status')
    ->values('John Doe', 'john@example.com', 25, 'active')
    ->run();

if ($insertId === null) {
    echo "Insert failed: " . $queryBuilder->getError();
} else {
    echo "New user ID: " . $insertId;
}
```

### INSERT with Validation
```php
// Enhanced error handling catches invalid inputs
$result = $queryBuilder->insert('') // Empty table name
    ->columns('name')
    ->values('Test')
    ->run();

if ($result === null) {
    echo "Error caught: " . $queryBuilder->getError();
    // Output: "Table name cannot be empty for INSERT"
}
```

---

## UPDATE Queries

### Basic UPDATE
```php
$affectedRows = $queryBuilder->update('users')
    ->set('last_login', date('Y-m-d H:i:s'))
    ->set('login_count', 1)
    ->whereEqual('email', 'john@example.com')
    ->run();

if ($affectedRows === null) {
    echo "Update failed: " . $queryBuilder->getError();
} else {
    echo "Updated {$affectedRows} rows";
}
```

### UPDATE with Multiple Conditions
```php
$result = $queryBuilder->update('products')
    ->set('price', 299.99)
    ->set('discount', 10)
    ->whereEqual('category', 'electronics')
    ->whereLess('stock', 5)
    ->whereNotNull('supplier_id')
    ->run();
```

---

## DELETE Queries

### Safe DELETE (with WHERE validation)
```php
$deletedRows = $queryBuilder->delete('users')
    ->whereEqual('status', 'inactive')
    ->whereLess('last_login', '2022-01-01')
    ->run();

if ($deletedRows === null) {
    echo "Delete failed: " . $queryBuilder->getError();
} else {
    echo "Deleted {$deletedRows} rows";
}
```

### Unsafe DELETE Protection
```php
// This is automatically prevented for safety
$result = $queryBuilder->delete('users')->run();

if ($result === null) {
    echo $queryBuilder->getError();
    // Output: "DELETE queries must have WHERE conditions for safety"
}
```

---

## Enhanced WHERE Conditions

### All Available WHERE Methods
```php
$query = $queryBuilder->select('*')
    ->from('products')
    ->whereEqual('status', 'active')           // WHERE status = 'active'
    ->whereLike('name', 'phone')               // WHERE name LIKE '%phone%'
    ->whereBigger('price', 100)                // WHERE price > 100
    ->whereLess('stock', 50)                   // WHERE stock < 50
    ->whereBiggerEqual('rating', 4.0)          // WHERE rating >= 4.0
    ->whereLessEqual('discount', 20)           // WHERE discount <= 20
    ->whereNull('deleted_at')                  // WHERE deleted_at IS NULL
    ->whereNotNull('description')              // WHERE description IS NOT NULL
    ->whereBetween('created_at', '2023-01-01', '2023-12-31')  // BETWEEN dates
    ->whereIn('category', ['electronics', 'books', 'clothing']) // IN clause
    ->whereNotIn('brand', ['excluded1', 'excluded2'])          // NOT IN clause
    ->run();
```

### Complex WHERE with Table Joins
```php
$results = $queryBuilder->select('u.name', 'p.title')
    ->from('users', 'u')
    ->leftJoin('posts p ON u.id = p.user_id')
    ->whereEqual('u.status', 'active')
    ->whereEqual('p.published', 1)
    ->whereBigger('p.views', 1000)
    ->run();
```

---

## Pagination & Limiting

### Basic Pagination
```php
// Page 2, 20 items per page
$users = $queryBuilder->select('*')
    ->from('users')
    ->paginate(2, 20)  // LIMIT 20 OFFSET 20
    ->run();
```

### Descriptive Limit Methods
```php
$recentPosts = $queryBuilder->select('*')
    ->from('posts')
    ->skip(10)         // Skip first 10 records
    ->take(5)          // Take only 5 records
    ->run();
```

### First and Last Records
```php
// Get first 5 users ordered by creation date
$firstUsers = $queryBuilder->select('*')
    ->from('users')
    ->first(5, 'created_at')
    ->run();

// Get last 3 posts ordered by update date
$lastPosts = $queryBuilder->select('*')
    ->from('posts')
    ->last(3, 'updated_at')
    ->run();
```

### Pagination State Management
```php
$query = $queryBuilder->select('*')
    ->from('users')
    ->limit(10)
    ->offset(20);

echo "Current limit: " . $query->getLimit();     // 10
echo "Current offset: " . $query->getOffset();   // 20
echo "Is paginated: " . ($query->isPaginated() ? 'Yes' : 'No'); // Yes

// Reset pagination
$query->resetPagination();
echo "After reset: " . $query->__toString(); // No LIMIT/OFFSET
```

---

## Error Handling

### Unified Error Pattern
All query methods follow the same pattern: `result|null`

```php
function handleQueryResult($result, $queryBuilder, $operation) {
    if ($result === null) {
        echo "{$operation} failed: " . $queryBuilder->getError();
        return false;
    }
    
    echo "{$operation} successful";
    return true;
}

// Usage examples
$users = $queryBuilder->select('*')->from('users')->run();
handleQueryResult($users, $queryBuilder, 'SELECT');

$insertId = $queryBuilder->insert('users')->columns('name')->values('Test')->run();
handleQueryResult($insertId, $queryBuilder, 'INSERT');
```

### Builder-Level Error Handling
```php
// Errors are caught at the builder level
$query = $queryBuilder->select(); // Empty select
if ($queryBuilder->getError()) {
    echo "Builder error: " . $queryBuilder->getError();
    // Output: "SELECT query must specify at least one column"
}
```

---

## Transactions

### Basic Transaction Usage
```php
// Begin transaction
if (!$queryBuilder->beginTransaction()) {
    die("Failed to start transaction: " . $queryBuilder->getError());
}

try {
    // Perform multiple operations
    $userId = $queryBuilder->insert('users')
        ->columns('name', 'email')
        ->values('John Doe', 'john@example.com')
        ->run();
    
    if ($userId === null) {
        throw new Exception("User insert failed");
    }
    
    $profileId = $queryBuilder->insert('user_profiles')
        ->columns('user_id', 'bio')
        ->values($userId, 'User bio')
        ->run();
    
    if ($profileId === null) {
        throw new Exception("Profile insert failed");
    }
    
    // Commit if all operations succeed
    if (!$queryBuilder->commit()) {
        throw new Exception("Commit failed: " . $queryBuilder->getError());
    }
    
    echo "Transaction completed successfully";
    
} catch (Exception $e) {
    // Rollback on any error
    if (!$queryBuilder->rollback()) {
        echo "Rollback failed: " . $queryBuilder->getError();
    }
    echo "Transaction failed: " . $e->getMessage();
}
```

---

## Integration with Table Classes

### Using QueryBuilder with Table Classes
```php
use App\Table\UserTable;

class UserService {
    private QueryBuilder $queryBuilder;
    private UserTable $userTable;
    
    public function __construct() {
        $this->queryBuilder = new QueryBuilder();
        $this->userTable = new UserTable();
    }
    
    public function getActiveUsers(): array {
        // Use QueryBuilder for complex queries
        return $this->queryBuilder->select('*')
            ->from('users')
            ->whereEqual('status', 'active')
            ->whereBigger('last_login', date('Y-m-d', strtotime('-30 days')))
            ->orderBy('name')
            ->run() ?? [];
    }
    
    public function createUser(array $data): ?int {
        // Use Table class for simple CRUD
        $this->userTable->name = $data['name'];
        $this->userTable->email = $data['email'];
        return $this->userTable->insert();
    }
    
    public function getUserStats(): array {
        return $this->queryBuilder->select('status', 'COUNT(*) as count')
            ->from('users')
            ->whereNotNull('email')
            ->run() ?? [];
    }
}
```

---

## Best Practices

### 1. Always Handle Errors
```php
// ✅ Good
$result = $queryBuilder->select('*')->from('users')->run();
if ($result === null) {
    // Handle error appropriately
    $this->logger->error('Query failed: ' . $queryBuilder->getError());
    return [];
}

// ❌ Bad - ignoring potential errors
$result = $queryBuilder->select('*')->from('users')->run();
foreach ($result as $user) { // Could fail if $result is null
    // ...
}
```

### 2. Use Parameter Binding (Automatic)
```php
// ✅ Automatically safe from SQL injection
$users = $queryBuilder->select('*')
    ->from('users')
    ->whereEqual('email', $userInput) // Automatically bound as parameter
    ->run();
```

### 3. Leverage Method Chaining
```php
// ✅ Clean and readable
$results = $queryBuilder
    ->select('u.name', 'p.title')
    ->from('users', 'u')
    ->leftJoin('posts p ON u.id = p.user_id')
    ->whereEqual('u.status', 'active')
    ->orderBy('u.name')
    ->paginate($page, $perPage)
    ->run();
```

### 4. Use Transactions for Related Operations
```php
// ✅ Group related operations
$queryBuilder->beginTransaction();
// ... multiple related queries
$queryBuilder->commit();
```

### 5. Choose the Right Tool
```php
// ✅ Use QueryBuilder for complex queries
$complexData = $queryBuilder->select('*')
    ->from('orders', 'o')
    ->leftJoin('order_items oi ON o.id = oi.order_id')
    ->whereBetween('o.created_at', $startDate, $endDate)
    ->run();

// ✅ Use Table classes for simple CRUD
$user = new UserTable();
$user->name = 'John';
$user->insert();
```

---

## Summary of Enhanced Features

### ✅ What's New and Improved:

1. **Unified Return Pattern** - All methods return `result|null` for consistent error handling
2. **Enhanced WHERE Conditions** - `whereIn()`, `whereNotIn()`, `whereBetween()` with proper parameter binding
3. **Modern SQL Syntax** - Standard `LIMIT ... OFFSET ...` instead of MySQL-specific syntax
4. **Comprehensive Validation** - Parameter validation prevents errors and security issues
5. **Safety Features** - DELETE queries require WHERE conditions, invalid inputs are handled gracefully
6. **Transaction Support** - Built-in transaction methods with proper error handling
7. **Pagination Methods** - Easy `paginate()`, `skip()`, `take()`, `first()`, `last()` methods
8. **Better Integration** - Seamless integration with Table/PdoQuery architecture
9. **Enhanced Documentation** - Comprehensive PHPDoc comments and type hints
10. **Cross-Database Compatibility** - Works with MySQL, PostgreSQL, SQLite

The enhanced QueryBuilder provides a **modern, safe, and powerful** way to build database queries while maintaining the simplicity and elegance of the fluent interface! 🚀 