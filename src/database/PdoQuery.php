<?php

namespace Gemvc\Database;
/**
 * PdoQuery extends QueryExecuter to provide high-level methods for common database operations
 */
class PdoQuery extends QueryExecuter
{
    /**
     * Constructor initializes the parent QueryExecuter class
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute an INSERT query and return the last inserted ID
     * 
     * @param string $insertQuery The SQL INSERT query
     * @param array<string, mixed> $arrayBindKeyValue Key-value pairs for parameter binding
     * @return int|false The last inserted ID or false on failure
     */
    public function insertQuery(string $insertQuery, array $arrayBindKeyValue = []): int|false
    {
        if (!$this->validateConnection()) {
            $this->setError('Database connection not established or invalid');
            return false;
        }

        try {
            if ($this->executeQuery($insertQuery, $arrayBindKeyValue)) {
                $lastId = $this->getLastInsertedId();
                return is_string($lastId) ? (int)$lastId : false;
            }
            return false;
        } catch (\PDOException $e) {
            $this->handleQueryError('Insert', $e);
            return false;
        } finally {
            $this->checkPoolStatus();
        }
    }

    /**
     * Execute an UPDATE query and return the number of affected rows
     * 
     * @param string $updateQuery The SQL UPDATE query
     * @param array<string, mixed> $arrayBindKeyValue Key-value pairs for parameter binding
     * @return int|null Number of affected rows (0 if no changes, null on error)
     */
    public function updateQuery(string $updateQuery, array $arrayBindKeyValue = []): ?int
    {
        if (!$this->validateConnection()) {
            $this->setError('Database connection not established or invalid');
            return null;
        }

        try {
            if ($this->executeQuery($updateQuery, $arrayBindKeyValue)) {
                $affectedRows = $this->getAffectedRows();
                if ($affectedRows === 0) {
                    $this->setError('No rows were updated. The data might be unchanged.');
                }
                return $affectedRows;
            }
            return null;
        } catch (\PDOException $e) {
            $this->handleQueryError('Update', $e);
            return null;
        } finally {
            $this->checkPoolStatus();
        }
    }

    /**
     * Execute a query with parameter binding
     * 
     * @param string $query The SQL query to execute
     * @param array<string, mixed> $arrayBind Key-value pairs for parameter binding
     * @return bool True on success, false on failure
     * @throws \PDOException On connection issues
     */
    private function executeQuery(string $query, array $arrayBind): bool
    {
        if (!$this->validateConnection()) {
            throw new \PDOException('Database connection not established or invalid');
        }

        try {
            $this->query($query);
            
            if ($this->getError() !== null) {
                return false;
            }
            
            foreach ($arrayBind as $key => $value) {
                $this->bind($key, $value);
            }
            
            return $this->execute();
        } catch (\PDOException $e) {
            $this->handleQueryError('Query execution', $e);
            return false;
        }
    }

    /**
     * Execute a DELETE query and return the number of affected rows
     * 
     * @param string $deleteQuery The SQL DELETE query
     * @param array<string, mixed> $arrayBindKeyValue Key-value pairs for parameter binding
     * @return int|null|false Number of affected rows, null on error, false on connection failure
     */
    public function deleteQuery(string $deleteQuery, array $arrayBindKeyValue = []): int|null|false
    {
        if (!$this->validateConnection()) {
            $this->setError('Database connection not established or invalid');
            return false;
        }

        try {
            if ($this->executeQuery($deleteQuery, $arrayBindKeyValue)) {
                $affectedRows = $this->getAffectedRows();
                if ($affectedRows === 0) {
                    $this->setError('No rows were deleted. The specified record might not exist.');
                }
                return $affectedRows;
            }
            return null;
        } catch (\PDOException $e) {
            $this->handleQueryError('Delete', $e);
            return null;
        } finally {
            $this->checkPoolStatus();
        }
    }

    /**
     * Execute a SELECT query and return results as objects
     * 
     * @param string $selectQuery The SQL SELECT query
     * @param array<string, mixed> $arrayBindKeyValue Key-value pairs for parameter binding
     * @return array|false Array of objects or false on failure
     */
    public function selectQueryObjects(string $selectQuery, array $arrayBindKeyValue = []): array|false
    {
        if (!$this->validateConnection()) {
            $this->setError('Database connection not established or invalid');
            return false;
        }

        try {
            if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
                $result = $this->fetchAllObjects();
                if ($result === false) {
                    $this->setError('Failed to fetch results from the query');
                } elseif (empty($result)) {
                    $this->setError('Query executed successfully but returned no results');
                }
                return $result;
            }
            return false;
        } catch (\PDOException $e) {
            $this->handleQueryError('Select objects', $e);
            return false;
        } finally {
            $this->checkPoolStatus();
        }
    }

    /**
     * Execute a SELECT query and return results as associative arrays
     * 
     * @param string $selectQuery The SQL SELECT query
     * @param array<string, mixed> $arrayBindKeyValue Key-value pairs for parameter binding
     * @return array|false Array of rows or false on failure
     */
    public function selectQuery(string $selectQuery, array $arrayBindKeyValue = []): array|false
    {
        if (!$this->validateConnection()) {
            $this->setError('Database connection not established or invalid');
            return false;
        }

        try {
            if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
                $result = $this->fetchAll();
                if ($result === false) {
                    $this->setError('Failed to fetch results from the query');
                } elseif (empty($result)) {
                    $this->setError('Query executed successfully but returned no results');
                }
                return $result;
            }
            return false;
        } catch (\PDOException $e) {
            $this->handleQueryError('Select', $e);
            return false;
        } finally {
            $this->checkPoolStatus();
        }
    }

    /**
     * Execute a COUNT query and return the result as an integer
     * 
     * @param string $selectCountQuery The SQL SELECT COUNT query
     * @param array<string, mixed> $arrayBindKeyValue Key-value pairs for parameter binding
     * @return int|false The count result or false on failure
     */
    public function selectCountQuery(string $selectCountQuery, array $arrayBindKeyValue = []): int|false
    {
        if (!$this->validateConnection()) {
            $this->setError('Database connection not established or invalid');
            return false;
        }

        try {
            if ($this->executeQuery($selectCountQuery, $arrayBindKeyValue)) {
                $result = $this->fetchColumn();
                if ($result === false) {
                    $this->setError('Failed to fetch count result');
                    return false;
                }
                
                if (is_numeric($result)) {
                    return (int)$result;
                } else {
                    $this->setError('Count query did not return a numeric value');
                    return false;
                }
            }
            return false;
        } catch (\PDOException $e) {
            $this->handleQueryError('Count', $e);
            return false;
        } finally {
            $this->checkPoolStatus();
        }
    }

    /**
     * Handle query errors consistently
     * 
     * @param string $operation The operation that failed
     * @param \PDOException $e The exception that was thrown
     */
    private function handleQueryError(string $operation, \PDOException $e): void
    {
        $errorMessage = sprintf(
            '%s operation failed: %s (Code: %s)',
            $operation,
            $e->getMessage(),
            $e->getCode()
        );
        $this->setError($errorMessage);
        error_log($errorMessage . "\nStack trace: " . $e->getTraceAsString());
    }
}
