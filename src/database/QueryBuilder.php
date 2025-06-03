<?php

namespace Gemvc\Database;

use Gemvc\Database\Query\Delete;
use Gemvc\Database\Query\Insert;
use Gemvc\Database\Query\Select;
use Gemvc\Database\Query\Update;

/**
 * Enhanced Query Builder with improved error handling and integration
 * 
 * Build and run SQL queries with a fluent interface, integrated with our 
 * enhanced Table/PdoQuery architecture for consistent error handling and performance.
 */
class QueryBuilder
{
    /**
     * Stores the last executed query object for error retrieval
     */
    private ?QueryBuilderInterface $lastQuery = null;

    /**
     * Stores any error that occurs during query building or execution
     */
    private ?string $error = null;

    /**
     * PdoQuery instance for database operations with lazy loading
     */
    private ?PdoQuery $pdoQuery = null;

    /**
     * Create a SELECT query with enhanced error handling
     * 
     * @param string ...$select Column names to select
     * @return Select Query object for method chaining
     */
    public function select(string ...$select): Select
    {
        $this->clearError();
        
        // Validate parameters
        if (empty($select)) {
            $this->setError("SELECT query must specify at least one column");
            return new Select(['*']); // Return safe default
        }
        
        foreach ($select as $column) {
            if (empty(trim($column))) {
                $this->setError("Column name cannot be empty in SELECT");
                return new Select(['*']); // Return safe default
            }
        }

        $query = new Select($select);
        $query->setQueryBuilder($this);
        return $query;
    }

    /**
     * Create an INSERT query with validation
     * 
     * @param string $intoTableName Table name for insertion
     * @return Insert Query object for method chaining
     */
    public function insert(string $intoTableName): Insert
    {
        $this->clearError();
        
        // Validate table name
        if (empty(trim($intoTableName))) {
            $this->setError("Table name cannot be empty for INSERT");
            // Return a dummy insert to prevent fatal errors
            $query = new Insert('dummy_table');
            $query->setQueryBuilder($this);
            return $query;
        }

        $query = new Insert($intoTableName);
        $query->setQueryBuilder($this);
        return $query;
    }

    /**
     * Create an UPDATE query with validation
     * 
     * @param string $tableName Table name for update
     * @return Update Query object for method chaining
     */
    public function update(string $tableName): Update
    {
        $this->clearError();
        
        // Validate table name
        if (empty(trim($tableName))) {
            $this->setError("Table name cannot be empty for UPDATE");
            // Return a dummy update to prevent fatal errors
            $query = new Update('dummy_table');
            $query->setQueryBuilder($this);
            return $query;
        }

        $query = new Update($tableName);
        $query->setQueryBuilder($this);
        return $query;
    }

    /**
     * Create a DELETE query with validation
     * 
     * @param string $tableName Table name for deletion
     * @return Delete Query object for method chaining
     */
    public function delete(string $tableName): Delete
    {
        $this->clearError();
        
        // Validate table name
        if (empty(trim($tableName))) {
            $this->setError("Table name cannot be empty for DELETE");
            // Return a dummy delete to prevent fatal errors
            $query = new Delete('dummy_table');
            $query->setQueryBuilder($this);
            return $query;
        }

        $query = new Delete($tableName);
        $query->setQueryBuilder($this);
        return $query;
    }
    
    /**
     * Get the error from the last executed query or builder operation
     * 
     * @return string|null The error message or null if no error occurred
     */
    public function getError(): ?string
    {
        // First check builder-level errors
        if ($this->error !== null) {
            return $this->error;
        }
        
        // Then check last query errors
        return $this->lastQuery?->getError();
    }
    
    /**
     * Set the last executed query object
     * This should be called by the query objects after execution
     * 
     * @param QueryBuilderInterface $query The query object that was executed
     */
    public function setLastQuery(QueryBuilderInterface $query): void
    {
        $this->lastQuery = $query;
    }

    /**
     * Set an error message at the builder level
     * 
     * @param string|null $error Error message to set
     */
    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    /**
     * Clear any existing error
     */
    public function clearError(): void
    {
        $this->error = null;
    }

    /**
     * Get a shared PdoQuery instance for consistent connection management
     * This provides lazy loading and connection reuse across queries
     * 
     * @return PdoQuery Database query executor
     */
    public function getPdoQuery(): PdoQuery
    {
        if ($this->pdoQuery === null) {
            $this->pdoQuery = new PdoQuery();
        }
        return $this->pdoQuery;
    }

    /**
     * Check if the builder has an active database connection
     * 
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->pdoQuery !== null && $this->pdoQuery->isConnected();
    }

    /**
     * Begin a database transaction
     * 
     * @return bool True on success, false on failure
     */
    public function beginTransaction(): bool
    {
        $result = $this->getPdoQuery()->beginTransaction();
        if (!$result) {
            $this->setError("Failed to begin transaction: " . $this->getPdoQuery()->getError());
        }
        return $result;
    }

    /**
     * Commit the current transaction
     * 
     * @return bool True on success, false on failure
     */
    public function commit(): bool
    {
        if (!$this->isConnected()) {
            $this->setError("No active connection to commit transaction");
            return false;
        }
        
        $result = $this->pdoQuery->commit();
        if (!$result) {
            $this->setError("Failed to commit transaction: " . $this->pdoQuery->getError());
        }
        return $result;
    }

    /**
     * Rollback the current transaction
     * 
     * @return bool True on success, false on failure
     */
    public function rollback(): bool
    {
        if (!$this->isConnected()) {
            $this->setError("No active connection to rollback transaction");
            return false;
        }
        
        $result = $this->pdoQuery->rollback();
        if (!$result) {
            $this->setError("Failed to rollback transaction: " . $this->pdoQuery->getError());
        }
        return $result;
    }

    /**
     * Force connection cleanup
     */
    public function disconnect(): void
    {
        if ($this->pdoQuery !== null) {
            $this->pdoQuery->disconnect();
            $this->pdoQuery = null;
        }
    }

    /**
     * Clean up resources
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
