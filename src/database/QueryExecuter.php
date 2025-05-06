<?php
namespace Gemvc\Database;
use Gemvc\Database\PdoConnection;
use PDO;
/**
 * execute query string with PDO by using PdoConnection Class
 */
class QueryExecuter
{
    private ?string $error = null;
    private int $affectedRows = 0;
    private string|false $lastInsertedId = false;
    private ?\PDOStatement $stsment = null;
    private float $startExecutionTime;
    private ?float $endExecutionTime = null;
    private string $_query = '';
    private ?PDO $db = null;
    private bool $isConnected = false;
    private ?PdoConnection $connection = null;


    /**
     * Constructor initializes execution timer but doesn't establish a database connection
     * Connection is only established when needed for query execution
     */
    public function __construct()
    {
        $this->startExecutionTime = microtime(true);
    }

    /**
     * Destructor ensures resources are properly cleaned up
     */
    public function __destruct()
    {
        $this->secure();
    }

    /**
     * Lazy-loads the database connection when needed
     * 
     * @return bool True if connection was successful, false otherwise
     */
    private function ensureConnection(): bool
    {
        // Only create connection if it doesn't exist or is not connected
        if ($this->db === null && !$this->isConnected) {
            $this->connection = new PdoConnection();
            $this->db = $this->connection->connect();
            $this->error = $this->connection->getError();
            $this->isConnected = $this->connection->isConnected();
        }
        
        return $this->isConnected;
    }

    /**
     * Get the current SQL query string
     * 
     * @return string|null The current SQL query or null if not set
     */
    public function getQuery(): null|string
    {
        return $this->_query ?: null;
    }

    /**
     * Check if database connection is active
     * 
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Prepare an SQL query for execution
     * Establishes a database connection if needed
     * 
     * @param string $query SQL query to prepare
     * @return void
     */
    public function query(string $query): void
    {
        $this->_query = $query;
        
        // Ensure we have a connection before preparing the query
        if (!$this->ensureConnection()) {
            $this->error = 'Database connection is null, please check your connection to Database';
            return;
        }
        
        try {
            $this->stsment = $this->db->prepare($query);
        } catch (\PDOException $e) {
            $this->error = 'Error preparing statement: ' . $e->getMessage();
        }
    }

    /**
     * Set an error message
     * 
     * @param string $error Error message
     * @return void
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }

    /**
     * Bind a parameter to the prepared statement with automatic type detection
     * 
     * @param string $param Parameter name or placeholder
     * @param mixed $value Value to bind
     * @return void
     */
    public function bind(string $param, mixed $value): void
    {
        if (!isset($this->stsment)) {
            $this->error = 'Cannot bind parameters: No statement prepared';
            return;
        }
        
        $type = match (true) {
            \is_int($value) => \PDO::PARAM_INT,
            null === $value => \PDO::PARAM_NULL,
            \is_bool($value) => \PDO::PARAM_BOOL,
            \is_string($value) => \PDO::PARAM_STR,
            default => \PDO::PARAM_STR,
        };
        $this->stsment->bindValue($param, $value, $type);
    }

    /**
     * Execute the prepared statement
     * Establishes a database connection if needed
     * 
     * @return bool True on success, false on failure
     */
    public function execute(): bool
    {
        // Ensure we have a connection before executing
        if (!$this->ensureConnection()) {
            $this->error = 'Database connection not established';
            $this->endExecutionTime = microtime(true);
            return false;
        }
        
        if (!isset($this->stsment)) {
            $this->error = 'No statement prepared';
            $this->endExecutionTime = microtime(true);
            return false;
        }
        
        try {
            $this->stsment->execute();
            $this->affectedRows = $this->stsment->rowCount();
            $this->lastInsertedId = $this->db->lastInsertId();
            $this->endExecutionTime = microtime(true);
            
            // Clear error if execution was successful
            $this->error = null;
            return true;

        } catch (\PDOException $e) {
            $this->endExecutionTime = microtime(true);
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Get the last error message
     * 
     * @return string|null Error message or null if no error
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get the number of rows affected by the last query
     * 
     * @return int|null Number of affected rows or null if execution failed
     */
    public function affectedRows(): int|null
    {
        return isset($this->error) ? null : $this->affectedRows;
    }

    /**
     * Get the ID of the last inserted row
     * 
     * @return string|false Last insert ID or false on failure
     */
    public function lastInsertId(): string|false
    {
        return $this->lastInsertedId;
    }

    /**
     * Release database resources and return connection to the pool
     * Connection pooling is managed by PdoConnection based on environment configuration
     * 
     * @return void
     */
    public function secure(): void
    {
        if (isset($this->stsment)) {
            $this->stsment->closeCursor();
            $this->stsment = null;
        }
        
        // Return the connection to the pool
        if ($this->connection !== null && $this->isConnected && $this->db !== null) {
            $this->connection->releaseConnection();
        }
        
        $this->db = null;
        $this->isConnected = false;
    }

    /**
     * Get the execution time of the last query in milliseconds
     * 
     * @return float Execution time in milliseconds or -1 if never executed
     */
    public function getExecutionTime(): float
    {
        if (isset($this->endExecutionTime)) {
            return ($this->endExecutionTime - $this->startExecutionTime) * 1000;
        }
        $this->error = 'Query never executed successfully, please check your query and try again';

        return -1;
    }

    /**
     * Fetch all rows as associative arrays
     * 
     * @return array|false Array of rows or false on failure
     */
    public function fetchAll(): array|false
    {
        if (!isset($this->stsment)) {
            $this->error = 'No statement to fetch results from';
            return false;
        }
        
        try {
            return $this->stsment->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Fetch all rows as stdClass objects
     * 
     * @return array|false Array of objects or false on failure
     */
    public function fetchAllObjects(): array|false
    {
        if (!isset($this->stsment)) {
            $this->error = 'No statement to fetch results from';
            return false;
        }
        
        try {
            return $this->stsment->fetchAll(\PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Fetch all rows as instances of a specific class
     * 
     * @param string $targetClassName Class name to map results to
     * @return array|false Array of objects or false on failure
     */
    public function fetchAllClass(string $targetClassName): array|false
    {
        if (!isset($this->stsment)) {
            $this->error = 'No statement to fetch results from';
            return false;
        }
        
        try {
            return $this->stsment->fetchAll(\PDO::FETCH_CLASS, $targetClassName);
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Fetch a single column from the first row
     * 
     * @return mixed Column value or false on failure
     */
    public function fetchColumn(): mixed
    {
        if (!isset($this->stsment)) {
            $this->error = 'No statement to fetch results from';
            return false;
        }
        
        try {
            return $this->stsment->fetchColumn();
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}
