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
        if (!$this->isConnected()) {
            $this->setError('Database connection not established');
            return false;
        }

        try {
            if ($this->executeQuery($insertQuery, $arrayBindKeyValue)) {
                $lastId = $this->getLastInsertedId();
                return is_string($lastId) ? (int)$lastId : false;
            }
            return false;
        } catch (\PDOException $e) {
            $this->setError('Insert operation failed: ' . $e->getMessage());
            return false;
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
        if (!$this->isConnected()) {
            $this->setError('Database connection not established');
            return null;
        }

        try {
            if ($this->executeQuery($updateQuery, $arrayBindKeyValue)) {
                $affectedRows = $this->getAffectedRows();
                if ($affectedRows === 0) {
                    // This is not an error, just informational
                    $this->setError('No rows were updated. The data might be unchanged.');
                }
                return $affectedRows;
            }
            // The executeQuery failed, error is already set
            return null;
        } catch (\PDOException $e) {
            // Check for specific error codes that might indicate "no changes"
            if ($e->getCode() == '00000' && strpos($e->getMessage(), 'no affected rows') !== false) {
                $this->setError('No changes were made. The data might be identical.');
                return 0;
            }
            
            // For other errors, set the error message
            $this->setError('Update operation failed: ' . $e->getMessage());
            return null;
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
        if (!$this->isConnected()) {
            throw new \PDOException('Database connection not established');
        }

        try {
            $this->query($query);
            
            // Check if query preparation was successful
            if ($this->getError() !== null) {
                return false;
            }
            
            foreach ($arrayBind as $key => $value) {
                $this->bind($key, $value);
            }
            
            return $this->execute();
        } catch (\PDOException $e) {
            $this->setError('Query execution failed: ' . $e->getMessage());
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
        if (!$this->isConnected()) {
            $this->setError('Database connection not established');
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
            // executeQuery failed, error is already set
            return null;
        } catch (\PDOException $e) {
            $this->setError('Delete operation failed: ' . $e->getMessage());
            return null;
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
        if (!$this->isConnected()) {
            $this->setError('Database connection not established');
            return false;
        }

        try {
            if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
                $result = $this->fetchAllObjects();
                if ($result === false) {
                    $this->setError('Failed to fetch results from the query');
                } elseif (empty($result)) {
                    // Empty result is not an error, just informational
                    $this->setError('Query executed successfully but returned no results');
                }
                return $result;
            }
            // executeQuery failed, error is already set
            return false;
        } catch (\PDOException $e) {
            $this->setError('Select operation failed: ' . $e->getMessage());
            return false;
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
        if (!$this->isConnected()) {
            $this->setError('Database connection not established');
            return false;
        }

        try {
            if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
                $result = $this->fetchAll();
                if ($result === false) {
                    $this->setError('Failed to fetch results from the query');
                } elseif (empty($result)) {
                    // Empty result is not an error, just informational
                    $this->setError('Query executed successfully but returned no results');
                }
                return $result;
            }
            // executeQuery failed, error is already set
            return false;
        } catch (\PDOException $e) {
            $this->setError('Select operation failed: ' . $e->getMessage());
            return false;
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
        if (!$this->isConnected()) {
            $this->setError('Database connection not established');
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
            // executeQuery failed, error is already set
            return false;
        } catch (\PDOException $e) {
            $this->setError('Count operation failed: ' . $e->getMessage());
            return false;
        }
    }
}
