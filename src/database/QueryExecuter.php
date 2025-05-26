<?php
namespace Gemvc\Database;

use PDO;
use PDOStatement;
use Gemvc\Database\DBPoolManager;

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

    /** @var bool Connection status */
    private bool $isConnected = false;

    /** @var array Bound parameters */
    private array $bindings = [];

    /** @var int Query timeout in seconds */
    private int $queryTimeout = 30;

    /** @var bool Transaction status */
    private bool $inTransaction = false;

    public function __construct()
    {
        $this->startExecutionTime = microtime(true);
        $this->queryTimeout = (int)($_ENV['DB_QUERY_TIMEOUT'] ?? 30);
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

    public function isConnected(): bool
    {
        $this->debug("Debug - QueryExecuter::isConnected() called\n");
        $this->debug("Debug - Current connection status: " . ($this->isConnected ? 'true' : 'false') . "\n");
        $this->debug("Debug - DB instance exists: " . ($this->db !== null ? 'yes' : 'no') . "\n");
        
        if (!$this->isConnected && $this->db === null) {
            $this->debug("Debug - Attempting to ensure connection...\n");
            $result = $this->ensureConnection();
            if (!$result) {
                $this->debug("Debug - Connection failed: " . $this->getError() . "\n");
            }
            return $result;
        }
        
        return $this->isConnected;
    }

    private function ensureConnection(): bool
    {
        $this->debug("Debug - Ensuring connection in QueryExecuter\n");
        
        if (!$this->db) {
            try {
                $this->debug("Debug - Getting connection from pool manager\n");
                $this->db = DBPoolManager::getInstance()->getConnection();
                $this->debug("Debug - Got connection from pool manager\n");
                
                $this->isConnected = $this->db instanceof PDO;
                $this->debug("Debug - Connection is PDO instance: " . ($this->isConnected ? 'yes' : 'no') . "\n");
                
                if ($this->isConnected) {
                    $this->debug("Debug - Connection successful, setting attributes\n");
                    // Set connection attributes
                    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                    
                    try {
                        $this->db->setAttribute(PDO::ATTR_TIMEOUT, $this->queryTimeout);
                        $this->debug("Debug - Connection attributes set successfully\n");
                    } catch (\PDOException $e) {
                        $this->debug("Debug - Error setting timeout: " . $e->getMessage() . "\n");
                        $this->setError('Error setting timeout: ' . $e->getMessage());
                        return false;
                    }
                } else {
                    $this->debug("Debug - Connection failed: Not a PDO instance\n");
                    $this->setError('Connection failed: Not a PDO instance');
                }
            } catch (\Throwable $e) {
                $this->debug("Debug - Failed to get DB connection: " . $e->getMessage() . "\n");
                $this->setError('Failed to get DB connection: ' . $e->getMessage());
                $this->isConnected = false;
            }
        } else {
            $this->debug("Debug - Connection already exists\n");
        }
        return $this->isConnected;
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
        // Reset error state for new query
        $this->setError(null);

        if ($this->inTransaction) {
            $this->setError('Cannot prepare new query while in transaction');
            return;
        }

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

        if (!$this->ensureConnection()) {
            return;
        }

        try {
            $this->statement = $this->db->prepare($query);
        } catch (\PDOException $e) {
            $this->setError('Error preparing statement: ' . $e->getMessage());
        }
    }

    public function setError(?string $error): void
    {
        $this->error = $error ?? '';
    }

    public function bind(string $param, mixed $value): void
    {
        // Reset error state for new binding
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

    private function getBindings(): array
    {
        return $this->bindings;
    }

    public function execute(): bool
    {
        // Reset error state for execution
        $this->setError(null);
        $this->affectedRows = 0;
        $this->lastInsertedId = false;

        if (!$this->ensureConnection()) {
            return false;
        }

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

        if (!$this->isConnected) {
            $this->setError('No active connection for fetching objects.');
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

        if (!$this->isConnected) {
            $this->setError('No active connection for fetching results.');
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

        if (!$this->isConnected) {
            $this->setError('No active connection for fetching column.');
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

    public function secure(): void
    {
        // If in transaction, rollback first
        if ($this->inTransaction && $this->db) {
            try {
                $this->db->rollBack();
            } catch (\PDOException $e) {
                // Log rollback error but continue with cleanup
                error_log('Error during rollback in secure(): ' . $e->getMessage());
            }
        }

        // First close the statement if it exists
        if ($this->statement) {
            try {
                $this->statement->closeCursor();
            } catch (\PDOException $e) {
                error_log('Error closing cursor in secure(): ' . $e->getMessage());
            }
            $this->statement = null;
        }

        // Then close the database connection if it exists
        if ($this->db) {
            try {
                // Release back to pool if using pool manager
                DBPoolManager::getInstance()->release($this->db);
            } catch (\Throwable $e) {
                error_log('Error releasing connection in secure(): ' . $e->getMessage());
            }
            $this->db = null;
        }

        // Finally update the connection state
        $this->isConnected = false;
        $this->inTransaction = false;
        $this->query = '';
        $this->bindings = [];
        $this->error = null;
        $this->affectedRows = 0;
        $this->lastInsertedId = false;
    }

    public function releaseConnection(): void
    {
        $this->secure();
    }

    public function beginTransaction(): bool
    {
        // Reset error state for transaction
        $this->setError(null);

        if ($this->inTransaction) {
            $this->setError('Already in transaction');
            return false;
        }

        if (!$this->ensureConnection()) {
            return false;
        }

        try {
            $this->inTransaction = $this->db->beginTransaction();
            return $this->inTransaction;
        } catch (\PDOException $e) {
            $this->setError('Error starting transaction: ' . $e->getMessage());
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

        if ($this->affectedRows === 0 && empty($this->lastInsertedId)) {
            $this->setError('No changes to commit');
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
