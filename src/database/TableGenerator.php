<?php

namespace Gemvc\Database;
use Gemvc\Database\QueryExecuter;

/**
 * TableGenerator automatically creates database tables from PHP objects using reflection.
 * 
 * This class analyzes object properties and creates appropriate database tables with
 * column types determined by the PHP property types. It provides a simple ORM-like
 * functionality for schema generation.
 */
class TableGenerator extends QueryExecuter {
    /**
     * @var array Array of property names to create indexes for
     */
    private array $indexedProperties = [];
    
    /**
     * @var array Array of property names to create unique indexes for
     */
    private array $uniqueIndexedProperties = [];
    
    /**
     * @var array Additional column properties like NOT NULL, DEFAULT, etc.
     */
    private array $columnProperties = [];

    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a table from an object
     * @param object $object The object to create a table from
     * @param string|null $tableName The name of the table to create
     * @return bool True if the table was created successfully, false otherwise
     */
    public function createTableFromObject(object $object, string $tableName = null): bool {
        echo "Debug - Starting table creation from object\n";
        
        echo "Debug - Checking connection status...\n";
        if (!$this->isConnected()) {
            echo "Debug - Connection check failed in TableGenerator\n";
            echo "Debug - Error message: " . $this->getError() . "\n";
            $this->setError("Database is not connected");
            return false;
        }
        echo "Debug - Connection check passed\n";

        // Get table name from object if not provided
        if (!$tableName) {
            if (!method_exists($object, 'getTable')) {
                $this->setError("public function getTable() not found in object");
                return false;
            }
            $tableName = $object->getTable();
            if (!$tableName) {
                $this->setError("function getTable() returned null string. Please define it and give table a name");
                return false;
            }
        }
        echo "Debug - Table name: " . $tableName . "\n";

        // Validate table name format
        if (!$this->isValidTableName($tableName)) {
            $this->setError("Invalid table name format: $tableName");
            return false;
        }

        $reflection = new \ReflectionClass($object);
        $properties = $reflection->getProperties();
        
        $columns = [];
        $propertyNames = []; // Keep track of property names for index creation
        
        foreach ($properties as $property) {
            $property->setAccessible(true); // Allow access to protected/private properties
            $propertyName = $property->getName();
            
            // Skip properties that shouldn't be in the database
            if ($this->shouldSkipProperty($property)) {
                continue;
            }

            // Get property type and determine SQL type
            $propertyType = $this->getPropertyType($property, $object);
            $sqlType = $this->mapTypeToSqlType($propertyType, $propertyName);
            
            // Add additional column properties if defined
            if (isset($this->columnProperties[$propertyName])) {
                $sqlType .= ' ' . $this->columnProperties[$propertyName];
            }
            
            $columns[] = "`$propertyName` $sqlType";
            $propertyNames[] = $propertyName;
        }

        // If no columns were found, return error
        if (empty($columns)) {
            $this->setError("No valid properties found in object to create table columns");
            return false;
        }

        $columnsSql = implode(", ", $columns);
        
        // Generate indexes SQL
        $indexesSql = $this->generateIndexesSql($tableName, $propertyNames);
        
        // Use backticks to escape table name
        $query = "CREATE TABLE IF NOT EXISTS `$tableName` ($columnsSql);";

        try {
            // Start transaction
            $this->query("START TRANSACTION");
            $this->execute();
            
            // Create table
            $this->query($query);
            if (!$this->execute()) {
                $this->query("ROLLBACK");
                $this->execute();
                return false;
            }
            
            // Create indexes if we have any
            if (!empty($indexesSql)) {
                foreach ($indexesSql as $indexSql) {
                    $this->query($indexSql);
                    if (!$this->execute()) {
                        $this->setError("Failed to create index: " . $this->getError());
                        $this->query("ROLLBACK");
                        $this->execute();
                        return false;
                    }
                }
            }
            
            // Commit transaction
            $this->query("COMMIT");
            $this->execute();
            
            return true;
        } catch (\Exception $exception) {
            $this->setError($exception->getMessage());
            // Ensure rollback on any exception
            $this->query("ROLLBACK");
            $this->execute();
            return false;
        }
    }
    
    /**
     * Mark a column in existing table as unique
     * 
     * @param string $tableName The name of the table
     * @param string $columnName The name of the column to make unique
     * @param bool $dropExistingIndex Whether to drop any existing index on this column first
     * @return bool True if successful, false otherwise
     */
    public function makeColumnUnique(string $tableName, string $columnName, bool $dropExistingIndex = true): bool {
        if (!$this->isConnected()) {
            $this->setError("Database is not connected");
            return false;
        }
        
        if (!$this->isValidTableName($tableName)) {
            $this->setError("Invalid table name format: $tableName");
            return false;
        }
        
        // Generate the index name
        $indexName = "uidx_{$tableName}_{$columnName}";
        
        try {
            // Start transaction
            $this->query("START TRANSACTION");
            $this->execute();
            
            // Check if column exists
            $this->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
            $result = $this->fetchAll();
            if (empty($result)) {
                $this->setError("Column '$columnName' does not exist in table '$tableName'");
                $this->query("ROLLBACK");
                $this->execute();
                return false;
            }
            
            // If requested, drop any existing indexes on this column
            if ($dropExistingIndex) {
                // Find any existing indexes on this column
                $this->query("SHOW INDEXES FROM `$tableName` WHERE Column_name = '$columnName'");
                $indexes = $this->fetchAll();
                
                foreach ($indexes as $index) {
                    $existingIndexName = $index['Key_name'];
                    // Don't drop primary key
                    if ($existingIndexName !== 'PRIMARY') {
                        $this->query("DROP INDEX `$existingIndexName` ON `$tableName`");
                        $this->execute();
                    }
                }
            }
            
            // Create the unique index
            $this->query("CREATE UNIQUE INDEX `$indexName` ON `$tableName` (`$columnName`)");
            if (!$this->execute()) {
                $this->setError("Failed to create unique index: " . $this->getError());
                $this->query("ROLLBACK");
                $this->execute();
                return false;
            }
            
            // Commit transaction
            $this->query("COMMIT");
            $this->execute();
            
            return true;
        } catch (\Exception $exception) {
            $this->setError($exception->getMessage());
            // Ensure rollback on any exception
            $this->query("ROLLBACK");
            $this->execute();
            return false;
        }
    }
    
    /**
     * Set column properties like NOT NULL, DEFAULT value, CHECK constraints, etc.
     * 
     * @param string $propertyName The name of the property
     * @param string $columnProperties SQL properties to add to the column definition
     * @return $this For method chaining
     */
    public function setColumnProperties(string $propertyName, string $columnProperties): self {
        $this->columnProperties[$propertyName] = $columnProperties;
        return $this;
    }
    
    /**
     * Set a column as NOT NULL
     * 
     * @param string $propertyName The name of the property
     * @return $this For method chaining
     */
    public function setNotNull(string $propertyName): self {
        $properties = $this->columnProperties[$propertyName] ?? '';
        if (strpos($properties, 'NOT NULL') === false) {
            $this->columnProperties[$propertyName] = trim($properties . ' NOT NULL');
        }
        return $this;
    }
    
    /**
     * Set a default value for a column
     * 
     * @param string $propertyName The name of the property
     * @param mixed $defaultValue The default value
     * @return $this For method chaining
     */
    public function setDefault(string $propertyName, mixed $defaultValue): self {
        $properties = $this->columnProperties[$propertyName] ?? '';
        
        // Handle different types of default values
        if (is_string($defaultValue)) {
            $defaultSql = "DEFAULT '" . $this->escapeString($defaultValue) . "'";
        } elseif (is_bool($defaultValue)) {
            $defaultSql = "DEFAULT " . ($defaultValue ? '1' : '0');
        } elseif (is_null($defaultValue)) {
            $defaultSql = "DEFAULT NULL";
        } else {
            $defaultSql = "DEFAULT " . $defaultValue;
        }
        
        // Remove any existing DEFAULT clause
        if (preg_match('/DEFAULT\s+[^,]+/', $properties)) {
            $properties = preg_replace('/DEFAULT\s+[^,]+/', $defaultSql, $properties);
        } else {
            $properties = trim($properties . ' ' . $defaultSql);
        }
        
        $this->columnProperties[$propertyName] = $properties;
        return $this;
    }
    
    /**
     * Add a CHECK constraint to a column
     * 
     * @param string $propertyName The name of the property
     * @param string $checkExpression The SQL expression for the check constraint
     * @return $this For method chaining
     */
    public function addCheck(string $propertyName, string $checkExpression): self {
        $properties = $this->columnProperties[$propertyName] ?? '';
        $checkSql = "CHECK ($checkExpression)";
        
        $this->columnProperties[$propertyName] = trim($properties . ' ' . $checkSql);
        return $this;
    }
    
    /**
     * Escape a string for SQL
     * 
     * @param string $value The string to escape
     * @return string The escaped string
     */
    private function escapeString(string $value): string {
        return str_replace("'", "''", $value);
    }
    
    /**
     * Mark a property to be indexed in the database
     * 
     * @param string $propertyName The name of the property to index
     * @param bool $unique Whether the index should be unique
     * @return $this For method chaining
     */
    public function addIndex(string $propertyName, bool $unique = false): self {
        if ($unique) {
            $this->uniqueIndexedProperties[] = $propertyName;
        } else {
            $this->indexedProperties[] = $propertyName;
        }
        return $this;
    }
    
    /**
     * Remove indexing from a property
     * 
     * @param string $propertyName The name of the property to remove indexing from
     * @return $this For method chaining
     */
    public function removeIndex(string $propertyName): self {
        $this->indexedProperties = array_filter($this->indexedProperties, function($prop) use ($propertyName) {
            return $prop !== $propertyName;
        });
        
        $this->uniqueIndexedProperties = array_filter($this->uniqueIndexedProperties, function($prop) use ($propertyName) {
            return $prop !== $propertyName;
        });
        
        return $this;
    }
    
    /**
     * Generate SQL statements for creating indexes
     * 
     * @param string $tableName The name of the table
     * @param array $validPropertyNames List of valid property names in the table
     * @return array Array of SQL statements for creating indexes
     */
    private function generateIndexesSql(string $tableName, array $validPropertyNames): array {
        $indexSqlStatements = [];
        
        // Process regular indexes
        foreach ($this->indexedProperties as $property) {
            if (in_array($property, $validPropertyNames)) {
                $indexName = "idx_{$tableName}_{$property}";
                $indexSqlStatements[] = "CREATE INDEX `$indexName` ON `$tableName` (`$property`);";
            }
        }
        
        // Process unique indexes
        foreach ($this->uniqueIndexedProperties as $property) {
            if (in_array($property, $validPropertyNames)) {
                $indexName = "uidx_{$tableName}_{$property}";
                $indexSqlStatements[] = "CREATE UNIQUE INDEX `$indexName` ON `$tableName` (`$property`);";
            }
        }
        
        return $indexSqlStatements;
    }

    /**
     * Remove a column from an existing table
     * 
     * @param string $tableName The name of the table
     * @param string $columnName The name of the column to remove
     * @return bool True if successful, false otherwise
     */
    public function removeColumn(string $tableName, string $columnName): bool {
        if (!$this->isConnected()) {
            $this->setError("Database is not connected");
            return false;
        }
        
        if (!$this->isValidTableName($tableName)) {
            $this->setError("Invalid table name format: $tableName");
            return false;
        }
        
        try {
            // Start transaction
            $this->query("START TRANSACTION");
            $this->execute();
            
            // Check if column exists
            $this->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
            $result = $this->fetchAll();
            if (empty($result)) {
                $this->setError("Column '$columnName' does not exist in table '$tableName'");
                $this->query("ROLLBACK");
                $this->execute();
                return false;
            }
            
            // Check if the column is part of any indexes and drop them first
            $this->query("SHOW INDEXES FROM `$tableName` WHERE Column_name = '$columnName'");
            $indexes = $this->fetchAll();
            $processedIndexes = [];
            
            foreach ($indexes as $index) {
                $indexName = $index['Key_name'];
                
                // Skip already processed indexes and PRIMARY keys (needs special handling)
                if (in_array($indexName, $processedIndexes) || $indexName === 'PRIMARY') {
                    continue;
                }
                
                // If it's a PRIMARY KEY, we need to drop it differently
                if ($indexName === 'PRIMARY') {
                    $this->query("ALTER TABLE `$tableName` DROP PRIMARY KEY");
                } else {
                    $this->query("DROP INDEX `$indexName` ON `$tableName`");
                }
                
                if (!$this->execute()) {
                    $this->setError("Failed to drop index '$indexName' on column '$columnName': " . $this->getError());
                    $this->query("ROLLBACK");
                    $this->execute();
                    return false;
                }
                
                $processedIndexes[] = $indexName;
            }
            
            // Remove the column
            $this->query("ALTER TABLE `$tableName` DROP COLUMN `$columnName`");
            if (!$this->execute()) {
                $this->setError("Failed to remove column '$columnName': " . $this->getError());
                $this->query("ROLLBACK");
                $this->execute();
                return false;
            }
            
            // Commit transaction
            $this->query("COMMIT");
            $this->execute();
            
            return true;
        } catch (\Exception $exception) {
            $this->setError($exception->getMessage());
            // Ensure rollback on any exception
            $this->query("ROLLBACK");
            $this->execute();
            return false;
        }
    }

    /**
     * Update an existing table based on changes in object properties
     * 
     * This method compares the object's current properties with the existing table structure and:
     * - Adds columns for new properties
     * - Updates columns for properties with changed types
     * - Removes columns for properties that no longer exist (if removeExtraColumns is true)
     * 
     * @param object $object The object with updated properties
     * @param string|null $tableName The name of the table to update or null to use object's getTable() method
     * @param bool $removeExtraColumns Whether to remove columns that don't exist in the object
     * @return bool True if the update was successful, false otherwise
     */
    public function updateTable(object $object, string $tableName = null, bool $removeExtraColumns = false): bool {
        if (!$this->isConnected()) {
            $this->setError("Database is not connected");
            return false;
        }

        // Get table name from object if not provided
        if (!$tableName) {
            if (!method_exists($object, 'getTable')) {
                $this->setError("public function getTable() not found in object");
                return false;
            }
            $tableName = $object->getTable();
            if (!$tableName) {
                $this->setError("function getTable() returned null string. Please define it and give table a name");
                return false;
            }
        }

        // Validate table name format
        if (!$this->isValidTableName($tableName)) {
            $this->setError("Invalid table name format: $tableName");
            return false;
        }

        try {
            // Start transaction
            $this->query("START TRANSACTION");
            $this->execute();
            
            // Check if table exists
            $this->query("SHOW TABLES LIKE '$tableName'");
            $tableExists = !empty($this->fetchAll());
            
            if (!$tableExists) {
                // Table doesn't exist, create it from scratch
                $this->query("ROLLBACK");
                $this->execute();
                return $this->createTableFromObject($object, $tableName);
            }
            
            // Get existing table structure
            $this->query("DESCRIBE `$tableName`");
            $existingColumns = $this->fetchAll();
            
            // Create a map of column names to their definitions
            $columnMap = [];
            foreach ($existingColumns as $column) {
                $name = $column['Field'];
                $type = $column['Type'];
                $nullable = $column['Null'] === 'YES';
                $default = $column['Default'];
                $extra = $column['Extra'];
                
                $columnMap[$name] = [
                    'type' => $type,
                    'nullable' => $nullable,
                    'default' => $default,
                    'extra' => $extra
                ];
            }
            
            // Get object properties
            $reflection = new \ReflectionClass($object);
            $properties = $reflection->getProperties();
            
            // Track property names and changes
            $objectPropertyNames = [];
            $columnsToAdd = [];
            $columnsToModify = [];
            
            // Analyze properties
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $propertyName = $property->getName();
                
                // Skip properties that shouldn't be in the database
                if ($this->shouldSkipProperty($property)) {
                    continue;
                }
                
                $objectPropertyNames[] = $propertyName;
                
                // Get property type and determine SQL type
                $propertyType = $this->getPropertyType($property, $object);
                $sqlType = $this->mapTypeToSqlType($propertyType, $propertyName);
                
                // Add additional column properties if defined
                if (isset($this->columnProperties[$propertyName])) {
                    $sqlType .= ' ' . $this->columnProperties[$propertyName];
                }
                
                // Check if column exists in the table
                if (!isset($columnMap[$propertyName])) {
                    // Column doesn't exist, add it
                    $columnsToAdd[] = [
                        'name' => $propertyName,
                        'definition' => $sqlType
                    ];
                } else {
                    // Column exists, check if type matches
                    $existingType = strtolower($columnMap[$propertyName]['type']);
                    $newType = strtolower(preg_replace('/\s+.*$/', '', $sqlType)); // Remove extra attributes
                    
                    // Skip comparison for id columns as they often have special attributes
                    if ($propertyName === 'id') {
                        continue;
                    }
                    
                    // If types don't match, modify the column
                    if ($existingType !== $newType) {
                        $columnsToModify[] = [
                            'name' => $propertyName,
                            'definition' => $sqlType
                        ];
                    }
                }
            }
            
            // Track columns to remove (if requested)
            $columnsToRemove = [];
            if ($removeExtraColumns) {
                foreach (array_keys($columnMap) as $existingColumnName) {
                    if (!in_array($existingColumnName, $objectPropertyNames)) {
                        $columnsToRemove[] = $existingColumnName;
                    }
                }
            }
            
            // Execute changes
            
            // 1. Add new columns
            foreach ($columnsToAdd as $column) {
                $this->query("ALTER TABLE `$tableName` ADD COLUMN `{$column['name']}` {$column['definition']}");
                if (!$this->execute()) {
                    $this->setError("Failed to add column '{$column['name']}': " . $this->getError());
                    $this->query("ROLLBACK");
                    $this->execute();
                    return false;
                }
            }
            
            // 2. Modify existing columns
            foreach ($columnsToModify as $column) {
                $this->query("ALTER TABLE `$tableName` MODIFY COLUMN `{$column['name']}` {$column['definition']}");
                if (!$this->execute()) {
                    $this->setError("Failed to modify column '{$column['name']}': " . $this->getError());
                    $this->query("ROLLBACK");
                    $this->execute();
                    return false;
                }
            }
            
            // 3. Remove extra columns
            foreach ($columnsToRemove as $columnName) {
                // Use our existing method to safely remove columns
                $result = $this->removeColumn($tableName, $columnName);
                if (!$result) {
                    // Error message already set by removeColumn
                    $this->query("ROLLBACK");
                    $this->execute();
                    return false;
                }
            }
            
            // 4. Handle indexes
            $indexesSql = $this->generateIndexesSql($tableName, $objectPropertyNames);
            if (!empty($indexesSql)) {
                // Get existing indexes to avoid duplicates
                $this->query("SHOW INDEXES FROM `$tableName`");
                $existingIndexes = $this->fetchAll();
                $existingIndexNames = [];
                
                foreach ($existingIndexes as $index) {
                    $existingIndexNames[] = $index['Key_name'];
                }
                
                // Create new indexes, skipping any that already exist
                foreach ($indexesSql as $indexSql) {
                    // Extract index name from SQL statement
                    if (preg_match('/CREATE\s+(UNIQUE\s+)?INDEX\s+`([^`]+)`/', $indexSql, $matches)) {
                        $indexName = $matches[2];
                        
                        // Skip if index already exists
                        if (in_array($indexName, $existingIndexNames)) {
                            continue;
                        }
                        
                        $this->query($indexSql);
                        if (!$this->execute()) {
                            $this->setError("Failed to create index: " . $this->getError());
                            $this->query("ROLLBACK");
                            $this->execute();
                            return false;
                        }
                    }
                }
            }
            
            // Commit transaction
            $this->query("COMMIT");
            $this->execute();
            
            return true;
        } catch (\Exception $exception) {
            $this->setError($exception->getMessage());
            // Ensure rollback on any exception
            $this->query("ROLLBACK");
            $this->execute();
            return false;
        }
    }

    /*-------------------------------------------private methods-------------------------------------------*/

    /**
     * Determines if a property should be skipped during table creation
     * 
     * @param \ReflectionProperty $property The property to check
     * @return bool True if the property should be skipped
     */
    private function shouldSkipProperty(\ReflectionProperty $property): bool {
        // Skip static properties
        if ($property->isStatic()) {
            return true;
        }
        
        // Skip properties that start with an underscore (convention for non-persisted properties)
        if (str_starts_with($property->getName(), '_')) {
            return true;
        }
        
        // Skip constants (class constants should not be database columns)
        if ($property->isReadOnly() && $property->isPublic()) {
            // In PHP 8.1+ we can use isReadOnly() to detect constants/final properties
            return true;
        }
        
        // Could add more conditions here, like checking for specific annotations
        // or property name patterns that indicate non-database fields

        return false;
    }

    /**
     * Get the type of a property
     * 
     * @param \ReflectionProperty $property The property to get the type of
     * @param object $object The object instance (for value type detection)
     * @return string The property type
     */
    private function getPropertyType(\ReflectionProperty $property, object $object): string {
        // Try to get type from PHP 7.4+ property type declaration
        if ($property->hasType()) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                return $type->getName();
            }
        }
        
        // Fallback to runtime type detection if property is accessible and initialized
        try {
            if ($property->isInitialized($object)) {
                $value = $property->getValue($object);
                $type = gettype($value);
                
                if ($type === 'object' && $value instanceof \DateTime) {
                    return 'DateTime';
                }
                
                return $type;
            }
        } catch (\Error | \Exception $e) {
            // Silently handle errors from accessing uninitialized properties
        }
        
        // Default to text if type can't be determined
        return 'unknown';
    }

    /**
     * Map PHP type to SQL column type
     * 
     * @param string $phpType The PHP type
     * @param string $propertyName The property name (for special handling)
     * @return string The SQL column type
     */
    private function mapTypeToSqlType(string $phpType, string $propertyName): string {
        $type = match(strtolower($phpType)) {
            'int', 'integer' => 'INT(11)',
            'float', 'double' => 'DOUBLE',
            'bool', 'boolean' => 'TINYINT(1)',
            'string' => 'VARCHAR(255)',
            'datetime' => 'DATETIME',
            'array' => 'JSON',
            'null', 'unknown' => 'TEXT',
            default => 'TEXT'
        };

        // Special handling for common field names
        if (strtolower($propertyName) === 'id') {
            $type .= ' AUTO_INCREMENT PRIMARY KEY';
        } elseif (str_ends_with(strtolower($propertyName), '_id')) {
            // Foreign key column - make it INT
            $type = 'INT(11)';
        } elseif (str_ends_with(strtolower($propertyName), 'email')) {
            // Email columns - make them VARCHAR(320) which is the max length of an email
            $type = 'VARCHAR(320)';
        }

        return $type;
    }

    /**
     * Validate if the table name is in a valid format
     * 
     * @param string $tableName The table name to validate
     * @return bool True if the table name is valid
     */
    private function isValidTableName(string $tableName): bool {
        // Table names should contain only alphanumeric characters, underscores, and should not start with a number
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName);
    }

    /**
     * Create a unique constraint across multiple columns
     * 
     * @param string $tableName The name of the table
     * @param array $columnNames Array of column names to include in the unique constraint
     * @param string|null $indexName Optional custom name for the index (defaults to auto-generated name)
     * @param bool $dropExistingIndexes Whether to drop any existing indexes on these columns
     * @return bool True if successful, false otherwise
     */
    public function makeColumnsUniqueTogether(string $tableName, array $columnNames, ?string $indexName = null, bool $dropExistingIndexes = true): bool {
        if (!$this->isConnected()) {
            $this->setError("Database is not connected");
            return false;
        }
        
        if (empty($columnNames)) {
            $this->setError("No column names provided for combined unique index");
            return false;
        }
        
        if (!$this->isValidTableName($tableName)) {
            $this->setError("Invalid table name format: $tableName");
            return false;
        }
        
        // Generate default index name if not provided
        if ($indexName === null) {
            // Create a name based on table and columns, with length limitation
            $columnsStr = implode('_', array_map(function($col) {
                return substr($col, 0, 5); // Take first 5 chars of each column name
            }, $columnNames));
            
            $indexName = "uidx_{$tableName}_{$columnsStr}";
            
            // Ensure index name isn't too long (MySQL has a 64 char limit)
            if (strlen($indexName) > 64) {
                $indexName = substr($indexName, 0, 60) . '_idx';
            }
        }
        
        try {
            // Start transaction
            $this->query("START TRANSACTION");
            $this->execute();
            
            // Check if all columns exist
            foreach ($columnNames as $columnName) {
                $this->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
                $result = $this->fetchAll();
                if (empty($result)) {
                    $this->setError("Column '$columnName' does not exist in table '$tableName'");
                    $this->query("ROLLBACK");
                    $this->execute();
                    return false;
                }
            }
            
            // If requested, drop any existing indexes on these columns
            if ($dropExistingIndexes) {
                $indexesToDrop = [];
                
                // For each column, find indexes that include it
                foreach ($columnNames as $columnName) {
                    $this->query("SHOW INDEXES FROM `$tableName` WHERE Column_name = '$columnName'");
                    $indexes = $this->fetchAll();
                    
                    foreach ($indexes as $index) {
                        $existingIndexName = $index['Key_name'];
                        // Don't drop primary key
                        if ($existingIndexName !== 'PRIMARY') {
                            $indexesToDrop[$existingIndexName] = true; // Use associative array to avoid duplicates
                        }
                    }
                }
                
                // Drop the identified indexes
                foreach (array_keys($indexesToDrop) as $indexToDrop) {
                    $this->query("DROP INDEX `$indexToDrop` ON `$tableName`");
                    $this->execute();
                }
            }
            
            // Create the combined columns list for the SQL
            $columnsListSql = implode('`, `', $columnNames);
            
            // Create the unique index on combined columns
            $this->query("CREATE UNIQUE INDEX `$indexName` ON `$tableName` (`$columnsListSql`)");
            if (!$this->execute()) {
                $this->setError("Failed to create combined unique index: " . $this->getError());
                $this->query("ROLLBACK");
                $this->execute();
                return false;
            }
            
            // Commit transaction
            $this->query("COMMIT");
            $this->execute();
            
            return true;
        } catch (\Exception $exception) {
            $this->setError($exception->getMessage());
            // Ensure rollback on any exception
            $this->query("ROLLBACK");
            $this->execute();
            return false;
        }
    }
} 