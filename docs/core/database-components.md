# Database Components

## Overview

GEMVC provides a robust database system with PDO-based connections, query execution, and connection pooling capabilities.

## Core Components

### 1. PdoConnection (`src/database/PdoConnection.php`)
- Connection management
- Connection pooling
- Connection health checks
- Error handling

### 2. QueryExecuter (`src/database/QueryExecuter.php`)
- Query preparation and execution
- Transaction handling
- Result fetching
- Error management
- Query timing

### 3. PdoQuery (`src/database/PdoQuery.php`)
- High-level query methods
- CRUD operations
- Parameter binding
- Result formatting

## Configuration

### Database Settings
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database
DB_USER=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
```

### Connection Pool Settings
```env
# Connection Pool Configuration
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=10
DB_CONNECTION_MAX_AGE=3600
DB_QUERY_TIMEOUT=30
```

## Basic Usage

### Database Connection
```php
use Gemvc\Database\PdoConnection;

$db = new PdoConnection();

// Get connection
$pdo = $db->connect();

// Check connection status
if ($db->isConnected()) {
    // Connection is active
}

// Release connection back to pool
$db->releaseConnection();
```

### Query Execution
```php
use Gemvc\Database\PdoQuery;

$query = new PdoQuery();

// Select query
$results = $query->selectQuery(
    'SELECT * FROM users WHERE status = :status',
    ['status' => 'active']
);

// Insert query
$id = $query->insertQuery(
    'INSERT INTO users (name, email) VALUES (:name, :email)',
    [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]
);

// Update query
$affected = $query->updateQuery(
    'UPDATE users SET status = :status WHERE id = :id',
    [
        'status' => 'inactive',
        'id' => 1
    ]
);

// Delete query
$deleted = $query->deleteQuery(
    'DELETE FROM users WHERE id = :id',
    ['id' => 1]
);
```

### Transaction Handling
```php
$query = new PdoQuery();

// Start transaction
if ($query->beginTransaction()) {
    try {
        // Execute queries
        $query->insertQuery('INSERT INTO users...');
        $query->updateQuery('UPDATE profiles...');
        
        // Commit transaction
        $query->commit();
    } catch (\Exception $e) {
        // Rollback on error
        $query->rollback();
        throw $e;
    }
}
```

## Advanced Features

### Connection Pooling
```php
use Gemvc\Database\DBPoolManager;

$pool = DBPoolManager::getInstance();

// Get connection from pool
$pdo = $pool->getConnection();

// Use connection
// ...

// Release connection back to pool
$pool->release($pdo);

// Get pool status
$status = $pool->getPoolStatus();
```

### Query Execution with Error Handling
```php
$query = new PdoQuery();

// Execute query with error handling
if ($query->executeQuery($sql, $params)) {
    $results = $query->fetchAll();
    if ($results === false) {
        // Handle fetch error
        $error = $query->getError();
    }
} else {
    // Handle execution error
    $error = $query->getError();
}
```

### Query Timing
```php
$query = new PdoQuery();

if ($query->executeQuery($sql, $params)) {
    $executionTime = $query->getExecutionTime(); // in milliseconds
}
```

## Best Practices

### 1. Connection Management
- Use connection pooling
- Release connections properly
- Handle connection errors
- Monitor connection health

### 2. Query Execution
- Use prepared statements
- Bind parameters properly
- Handle errors appropriately
- Monitor query performance

### 3. Transaction Handling
- Keep transactions short
- Handle rollbacks properly
- Use appropriate error handling
- Monitor transaction performance

### 4. Error Handling
- Check connection status
- Handle query errors
- Log database errors
- Implement retry logic

### 5. Performance
- Use connection pooling
- Monitor query execution time
- Optimize query parameters
- Handle large result sets

## Next Steps

- [Request Lifecycle](request-lifecycle.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 