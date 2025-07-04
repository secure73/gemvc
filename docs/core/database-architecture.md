# Database Architecture

## Directory Structure
```
src/database/
├── AbstractDatabasePool.php    # Base abstract class for pool management
├── OpenSwooleDatabasePool.php  # OpenSwoole-specific pool implementation
├── StandardDatabasePool.php    # Standard pool implementation
├── DatabasePoolFactory.php     # Factory for creating pool instances
├── QueryExecuter.php          # Low-level PDO operations and connection management
├── PdoQuery.php               # High-level query interface (uses QueryExecuter via composition)
├── Table.php                  # Base table abstraction with lazy loading
├── query/                     # Query building components
│   ├── Select.php             # SELECT query builder
│   ├── Insert.php             # INSERT query builder
│   ├── Update.php             # UPDATE query builder
│   ├── Delete.php             # DELETE query builder
│   ├── WhereTrait.php         # WHERE clause functionality
│   └── LimitTrait.php         # LIMIT clause functionality
├── QueryBuilder.php           # Query builder implementation
├── QueryBuilderInterface.php  # Query builder contract
├── TableGenerator.php         # Table generation utilities
└── SqlEnumCondition.php      # SQL condition enums

app/
├── table/                     # Table layer - single database table representations
│   ├── UserTable.php          # Users table with database properties
│   ├── ProfileTable.php       # Profiles table
│   └── OrderTable.php         # Orders table
└── model/                     # Model layer - business logic + multi-table operations
    ├── UserModel.php          # User business logic (extends UserTable)
    ├── OrderModel.php         # Order business logic (extends OrderTable)
    └── ProductModel.php       # Product business logic (extends ProductTable)
```

## Database Architecture Layers

The database system follows a clean two-layer architecture with lazy loading throughout:

```
Model Layer (Business Logic + Multi-Table Operations)
    ↓ (extends)
Table Layer (Single Database Table + Properties)
    ↓ (extends)
Base Table (Core Database Functionality)
    ↓ (composition with lazy loading)
PdoQuery → QueryExecuter → DatabasePoolFactory → Connection Pools
```

### Database Layer Responsibilities

1. **Model Layer**: Business logic, validation, multi-table operations, relationships
2. **Table Layer**: Single database table representation, table-specific operations

## Core Components

### 1. Connection Pool Management (with Lazy Loading)

#### DatabasePoolFactory
- Singleton factory for pool creation
- Environment detection (OpenSwoole vs Standard PHP)
- Lazy initialization - pools created only when needed
```php
class DatabasePoolFactory {
    private static ?AbstractDatabasePool $instance = null;
    
    public static function getInstance(): AbstractDatabasePool {
        if (self::$instance === null) {
            if (extension_loaded('openswoole')) {
                self::$instance = new OpenSwooleDatabasePool();
            } else {
                self::$instance = new StandardDatabasePool();
            }
        }
        return self::$instance;
    }
}
```

#### AbstractDatabasePool
- Base abstract class for pool management
- Connection lifecycle and health management
- Performance metrics and monitoring
```php
abstract class AbstractDatabasePool {
    protected array $activeConnections = [];
    protected array $connectionStats = [];
    protected string $error;
    protected int $maxPoolSize;
    protected int $maxConnectionAge;
    
    abstract public function getConnection(): PDO;
    abstract public function releaseConnection(PDO $connection): void;
    abstract public function validateConnection(PDO $connection): bool;
}
```

#### OpenSwooleDatabasePool & StandardDatabasePool
- Environment-specific implementations
- Coroutine-safe operations (OpenSwoole) vs traditional array-based (Standard)
- Automatic connection management and cleanup

### 2. Query Execution (Composition Architecture)

#### QueryExecuter
- Low-level PDO operations and connection management
- Transaction handling and resource cleanup
- Performance metrics and error handling
```php
class QueryExecuter {
    private ?string $error = null;
    private int $affectedRows = 0;
    private string|false $lastInsertedId = false;
    private ?PDOStatement $statement = null;
    private ?PDO $db = null;
    private bool $inTransaction = false;
    private AbstractDatabasePool $pool;
    
    public function __construct() {
        $this->pool = DatabasePoolFactory::getInstance(); // Lazy pool creation
    }
}
```

#### PdoQuery (Composition with Lazy Loading)
- **Uses QueryExecuter via composition (NOT inheritance)**
- High-level database operations with lazy connection initialization
- Automatic resource management and cleanup
```php
class PdoQuery {
    private ?QueryExecuter $executer = null;  // Lazy-loaded
    private bool $isConnected = false;
    
    private function getExecuter(): QueryExecuter {
        if ($this->executer === null) {
            $this->executer = new QueryExecuter(); // Connection created only here
            $this->isConnected = true;
        }
        return $this->executer;
    }
    
    public function insertQuery(string $query, array $bindings): int|false
    public function updateQuery(string $query, array $bindings): ?int
    public function deleteQuery(string $query, array $bindings): int|null|false
    public function selectQuery(string $query, array $bindings): array|false
}
```

### 3. Table Layer (Database Schema Representation)

#### Base Table
- Core database functionality with lazy loading
- Query building, CRUD operations, pagination
- Automatic exclusion of underscore-prefixed properties from database operations
```php
class Table extends PdoQuery {
    protected array $_type_map = [];  // Property type mapping
    
    // Methods automatically ignore properties starting with _
    private function getInsertBindings(): array {
        $arrayBind = [];
        foreach ($this as $key => $value) {
            if ($key[0] === '_') continue; // Skip underscore properties
            $arrayBind[':' . $key] = $value;
        }
        return $arrayBind;
    }
}
```

#### Table Classes (app/table/)
- Represent single database tables
- Define database column properties
- Table-specific query methods
```php
class UserTable extends Table {
    // Database columns only
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?bool $is_active = null;
    
    protected array $_type_map = [
        'id' => 'int',
        'is_active' => 'bool'
    ];
    
    public function getTable(): string {
        return 'users';
    }
}
```

### 4. Model Layer (Business Logic)

#### Model Classes (app/model/)
- Extend table classes for database access
- **Use underscore-prefixed properties for non-database data**
- Multi-table operations and business logic
```php
class UserModel extends UserTable {
    // Business logic methods
    public function getActiveUsers() {
        return $this->select('id, name, email')
            ->where('is_active', true)
            ->orderBy('created_at', false)
            ->run();
    }
    
    public function createUser(array $data) {
        // Map POST data to object properties
        $this->request->mapPostToObject($this);
        return $this->insertSingleQuery();
    }
    
    // Non-database properties (use underscore prefix)
    private string $_temp_data = '';
    private array $_cached_results = [];
}
```

## Key Features

### 1. Lazy Loading
- Database connections created only when needed
- PdoQuery instantiated on first use
- Error storage before connection creation
- Memory optimization

### 2. Type Safety
- Property type mapping with `_type_map`
- Automatic type casting
- Type validation on database operations
- Nullable property support

### 3. Security
- Prepared statements via QueryExecuter
- Parameter binding
- SQL injection prevention
- Input sanitization

### 4. Performance
- Connection pooling
- Query result caching
- Lazy loading
- Resource management

### 5. Environment Support
- Apache and OpenSwoole compatibility
- Environment-specific connection pools
- Coroutine-safe operations (OpenSwoole)

## Usage Examples

### Basic Table Class
```php
<?php
namespace App\Table;

use Gemvc\Database\Table;

class UserTable extends Table {
    // Database properties
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?bool $is_active = null;
    public ?string $created_at = null;
    
    // Type mapping for automatic casting
    protected array $_type_map = [
        'id' => 'int',
        'is_active' => 'bool'
    ];
    
    public function getTable(): string {
        return 'users';
    }
}
```

### Model with Business Logic
```php
<?php
namespace App\Model;

use App\Table\UserTable;

class UserModel extends UserTable {
    
    public function getActiveUsers() {
        return $this->select('id, name, email')
            ->where('is_active', true)
            ->orderBy('created_at', false)
            ->run();
    }
    
    public function createUser(array $data) {
        $this->request->mapPostToObject($this);
        return $this->insertSingleQuery();
    }
    
    public function updateUser(int $id, array $data) {
        $this->request->mapPostToObject($this);
        return $this->where('id', $id)->updateSingleQuery();
    }
    
    public function deleteUser(int $id) {
        return $this->deleteByIdQuery($id);
    }
}
```

### CRUD Operations
```php
// Create
$userModel = new UserModel();
$userModel->name = 'John Doe';
$userModel->email = 'john@example.com';
$result = $userModel->insertSingleQuery();

// Read
$users = $userModel->select('id, name, email')->run();
$user = $userModel->selectById(1);

// Update
$userModel->name = 'Jane Doe';
$result = $userModel->where('id', 1)->updateSingleQuery();

// Delete
$result = $userModel->deleteByIdQuery(1);
```

## Best Practices

### 1. Property Naming
- Use underscore prefix for non-database properties
- Database properties should match column names
- Use nullable types for optional fields

### 2. Type Mapping
- Define `_type_map` for automatic casting
- Use appropriate PHP types
- Handle nullable properties correctly

### 3. Error Handling
- Always check for null results
- Use `getError()` for error messages
- Log database errors appropriately

### 4. Performance
- Use specific columns in SELECT queries
- Implement pagination for large datasets
- Use appropriate WHERE conditions
- Leverage connection pooling

### 5. Security
- Always use Table class methods (prepared statements)
- Validate input data
- Use proper authentication and authorization
- Sanitize output data

## Next Steps

- [Table Usage Guide](../QueryBuilder-Usage-Guide.md)
- [CLI Database Commands](../cli/commands.md)
- [Performance Guide](../guides/performance.md)
