<?php
namespace Gemvc\Database;

use PDO;
use PDOStatement;
use Gemvc\Database\DatabasePoolFactory;

class QueryExecuter
{
    /** @var string|null Error message if any */
    private ?string $error = null;

    /** @var int Number of affected rows */
    private int $affectedRows = 0;

    /** @var string|false Last inserted ID */
    private string|false $lastInsertedId = false;

    /** @var PDOStatement|null Prepared statement */
    private ?PDOStatement $statement = null;

    /** @var float Query start time */
    private float $startExecutionTime;

    /** @var float|null Query end time */
    private ?float $endExecutionTime = null;

    /** @var string Current query */
    private string $query = '';

    /** @var PDO|null Database connection */
    private ?PDO $db = null;

    /** @var bool Transaction status */
    private bool $inTransaction = false;

    /** @var array Bound parameters */
    private array $bindings = [];

    /** @var AbstractDatabasePool Database pool instance */
    private AbstractDatabasePool $pool;

    public function __construct()
    {
        $this->startExecutionTime = microtime(true);
        $this->pool = DatabasePoolFactory::getInstance();
    }

    public function __destruct()
    {
        $this->secure();
    }

    private function debug(string $message): void
    {
        if (($_ENV['APP_ENV'] ?? '') === 'dev') {
            echo $message;
        }
    }

    public function getQuery(): ?string
    {
        return $this->query ?: null;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function query(string $query): void
    {
        $this->setError(null);

        if (empty($query)) {
            $this->setError('Query cannot be empty');
            return;
        }

        if (strlen($query) > 1000000) { // 1MB limit
            $this->setError('Query exceeds maximum length');
            return;
        }

        // Reset previous query state
        if ($this->statement) {
            $this->statement->closeCursor();
            $this->statement = null;
        }
        $this->bindings = [];
        $this->query = $query;

        try {
            // Get connection if not already in transaction
            if (!$this->db) {
                $this->db = $this->pool->getConnection();
            }
            $this->statement = $this->db->prepare($query);
        } catch (\Throwable $e) {
            $this->setError('Error preparing statement: ' . $e->getMessage());
            $this->releaseConnection();
        }
    }

    public function setError(?string $error): void
    {
        $this->error = $error ?? null;
    }

    public function bind(string $param, mixed $value): void
    {
        $this->setError(null);

        if (!$this->statement) {
            $this->setError('Cannot bind parameters: No statement prepared');
            return;
        }

        $type = match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };

        try {
            $this->statement->bindValue($param, $value, $type);
            $this->bindings[$param] = $value;
        } catch (\PDOException $e) {
            $this->setError('Error binding parameter: ' . $e->getMessage());
        }
    }

    public function execute(): bool
    {
        $this->setError(null);
        $this->affectedRows = 0;
        $this->lastInsertedId = false;

        if (empty($this->query)) {
            $this->setError('No query to execute');
            $this->endExecutionTime = microtime(true);
            return false;
        }

        if (!$this->statement) {
            $this->setError('No statement prepared');
            $this->endExecutionTime = microtime(true);
            return false;
        }

        try {
            $this->statement->execute();
            $this->affectedRows = $this->statement->rowCount();
            $this->lastInsertedId = $this->db->lastInsertId();
            $this->endExecutionTime = microtime(true);
            return true;
        } catch (\PDOException $e) {
            // Enhanced error logging with more details
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'errorInfo' => $e->errorInfo ?? [],
                'query' => $this->query,
                'bindings' => $this->bindings
            ];
            error_log("QueryExecuter::execute() - PDO Exception: " . json_encode($errorDetails));
            
            $this->setError($e->getMessage());
            $this->endExecutionTime = microtime(true);
            return false;
        }
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    public function getLastInsertedId(): false|string
    {
        return $this->lastInsertedId;
    }

    public function getExecutionTime(): float
    {
        return round(($this->endExecutionTime - $this->startExecutionTime) * 1000, 2);
    }

    public function fetchAllObjects(): array|false
    {
        if (!$this->statement) {
            $this->setError('No statement prepared for fetching objects.');
            return false;
        }

        try {
            $results = $this->statement->fetchAll(PDO::FETCH_OBJ);
            $this->statement->closeCursor();
            return $results;
        } catch (\PDOException $e) {
            $this->setError('Error fetching objects: ' . $e->getMessage());
            return false;
        }
    }

    public function fetchAll(): array|false
    {
        if (!$this->statement) {
            $this->setError('No statement prepared for fetching results.');
            return false;
        }

        try {
            $results = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            $this->statement->closeCursor();
            return $results;
        } catch (\PDOException $e) {
            $this->setError('Error fetching results: ' . $e->getMessage());
            return false;
        }
    }

    public function fetchColumn(): mixed
    {
        if (!$this->statement) {
            $this->setError('No statement prepared for fetching a column.');
            return false;
        }

        try {
            $result = $this->statement->fetchColumn();
            $this->statement->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            $this->setError('Error fetching column: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Securely clean up database resources
     * 
     * @param bool $forceRollback Whether to force rollback of active transactions
     */
    public function secure(bool $forceRollback = false): void
    {
        // Handle transaction if needed
        if ($this->inTransaction && $this->db) {
            try {
                if ($forceRollback) {
                    $this->db->rollBack();
                    $this->debug("Transaction rolled back in secure()");
                } else {
                    $this->debug("Active transaction found in secure() - not rolling back");
                }
            } catch (\PDOException $e) {
                error_log('Error during transaction handling in secure(): ' . $e->getMessage());
            }
        }

        // Close statement if exists
        if ($this->statement) {
            try {
                $this->statement->closeCursor();
                $this->debug("Statement cursor closed in secure()");
            } catch (\PDOException $e) {
                error_log('Error closing cursor in secure(): ' . $e->getMessage());
            }
            $this->statement = null;
        }

        // Release connection back to pool
        $this->releaseConnection();
    }

    /**
     * Release the current database connection back to the pool
     */
    private function releaseConnection(): void
    {
        if ($this->db) {
            try {
                $this->pool->releaseConnection($this->db);
                $this->debug("Connection released back to pool");
            } catch (\Throwable $e) {
                error_log('Error releasing connection: ' . $e->getMessage());
            }
            $this->db = null;
        }

        $this->inTransaction = false;
        $this->query = '';
        $this->bindings = [];
        $this->error = null;
        $this->affectedRows = 0;
        $this->lastInsertedId = false;
    }

    public function beginTransaction(): bool
    {
        $this->setError(null);

        if ($this->inTransaction) {
            $this->setError('Already in transaction');
            return false;
        }

        try {
            $this->db = $this->pool->getConnection();
            $this->inTransaction = $this->db->beginTransaction();
            return $this->inTransaction;
        } catch (\Throwable $e) {
            $this->setError('Error starting transaction: ' . $e->getMessage());
            $this->releaseConnection();
            return false;
        }
    }

    public function commit(): bool
    {
        if (!$this->inTransaction) {
            $this->setError('No active transaction');
            return false;
        }

        if (!$this->db) {
            $this->setError('No active connection for commit');
            return false;
        }

        try {
            $this->inTransaction = !$this->db->commit();
            return !$this->inTransaction;
        } catch (\PDOException $e) {
            $this->setError('Error committing transaction: ' . $e->getMessage());
            return false;
        }
    }

    public function rollback(): bool
    {
        if (!$this->inTransaction) {
            $this->setError('No active transaction');
            return false;
        }

        if (!$this->db) {
            $this->setError('No active connection for rollback');
            return false;
        }

        try {
            $this->inTransaction = !$this->db->rollBack();
            return !$this->inTransaction;
        } catch (\PDOException $e) {
            $this->setError('Error rolling back transaction: ' . $e->getMessage());
            return false;
        }
    }
}
