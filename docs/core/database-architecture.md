# Database Architecture

## Overview

GEMVC provides a robust database abstraction layer with type-safe properties, fluent query interface, and connection pooling.

## Core Components

### 1. Table Class (`src/database/Table.php`)
- Base class for all database tables
- Type-safe properties
- Fluent query interface
- Data persistence methods

### 2. PdoQuery (`src/database/PdoQuery.php`)
- Query builder
- SQL generation
- Parameter binding
- Query execution

### 3. QueryExecuter (`src/database/QueryExecuter.php`)
- Query execution
- Result processing
- Error handling
- Transaction management

### 4. PdoConnection (`src/database/PdoConnection.php`)
- Connection management
- Connection pooling
- Health verification
- Resource tracking

## Type System

### Property Types
```php
class UserTable extends Table
{
    public int $id;
    public string $username;
    public string $email;
    public bool $is_active = true;
    public ?string $bio = null;
    public array $permissions = [];
    
    protected array $_type_map = [
        'id' => 'int',
        'is_active' => 'bool',
        'permissions' => 'json'
    ];
}
```

### Type Mapping
- `int`: Integer values
- `string`: String values
- `bool`: Boolean values
- `float`: Floating-point values
- `array`: Array values (stored as JSON)
- `json`: JSON values
- `datetime`: DateTime values
- `date`: Date values
- `time`: Time values

## Query Building

### Select Queries
```php
$users = $table->select()
    ->where('is_active', true)
    ->where('age', '>', 18)
    ->whereIn('role', ['admin', 'user'])
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->offset(0)
    ->run();
```

### Insert Queries
```php
$userId = $table->insert([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'is_active' => true
])->run();
```

### Update Queries
```php
$table->update([
    'is_active' => false
])
->where('id', 1)
->run();
```

### Delete Queries
```php
$table->delete()
    ->where('id', 1)
    ->run();
```

## Connection Pooling

### Configuration
```env
# Connection Pool Settings
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=5
DB_CONNECTION_MAX_AGE=3600
DB_CONNECTION_TIME_OUT=20
DB_CONNECTION_EXPIER_TIME=20
```

### Pool Management
```php
// Get connection from pool
$connection = PdoConnection::getInstance()->getConnection();

// Release connection back to pool
$connection->release();
```

## Query Caching

### Configuration
```env
# Query Cache Settings
DB_CACHE_ENABLED=true
DB_CACHE_TTL_SEC=3600
DB_CACHE_MAX_QUERY_SIZE=1000
```

### Cache Usage
```php
// Cache query results
$users = $table->select()
    ->where('is_active', true)
    ->cache(3600)  // Cache for 1 hour
    ->run();

// Clear cache
$table->clearCache();
```

## Transactions

### Basic Transaction
```php
try {
    $table->beginTransaction();
    
    // Perform operations
    $table->insert([...])->run();
    $table->update([...])->run();
    
    $table->commit();
} catch (\Exception $e) {
    $table->rollback();
    throw $e;
}
```

### Nested Transactions
```php
try {
    $table->beginTransaction();
    
    // Outer transaction
    $table->insert([...])->run();
    
    try {
        $table->beginTransaction();
        
        // Inner transaction
        $table->update([...])->run();
        
        $table->commit();
    } catch (\Exception $e) {
        $table->rollback();
        throw $e;
    }
    
    $table->commit();
} catch (\Exception $e) {
    $table->rollback();
    throw $e;
}
```

## Error Handling

### Query Errors
```php
try {
    $result = $table->select()->run();
} catch (\Gemvc\Database\Exceptions\QueryException $e) {
    // Handle query error
    return (new JsonResponse())->error($e->getMessage());
}
```

### Connection Errors
```php
try {
    $connection = PdoConnection::getInstance()->getConnection();
} catch (\Gemvc\Database\Exceptions\ConnectionException $e) {
    // Handle connection error
    return (new JsonResponse())->error($e->getMessage());
}
```

## Best Practices

### 1. Type Safety
- Always define property types
- Use type mapping for complex types
- Validate data before insertion

### 2. Query Building
- Use fluent interface
- Chain methods for readability
- Use prepared statements

### 3. Connection Management
- Use connection pooling
- Release connections properly
- Monitor connection health

### 4. Caching
- Cache frequently used queries
- Set appropriate TTL
- Clear cache when data changes

### 5. Transactions
- Use transactions for multiple operations
- Handle rollbacks properly
- Keep transactions short

## Next Steps

- [Request Lifecycle](request-lifecycle.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 