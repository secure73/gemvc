<?php
namespace Gemvc\Database;

use PDO;
use Gemvc\Helper\TypeHelper;

/**
 * To connect Database and provide connection pooling and basic PDO functions.
 */
class PdoConnection
{
    private bool $isConnected = false;
    private ?string $error = null;
    private ?PDO $db = null;
    private ?DBConnection $connection = null;
    
    /**
     * Static connection pool to store and reuse database connections
     * Keyed by connection parameters hash
     * Each connection is stored with its creation timestamp
     * @var array<string, array<int, array{connection: DBConnection, created_at: int}>>
     */
    private static array $connectionPool = [];
    
    /**
     * Total number of active connections across all pools
     */
    private static int $totalConnections = 0;
    
    /**
     * Pool key based on connection parameters
     */
    private string $poolKey;
    
    /**
     * Unique instance identifier for tracking
     */
    private string $instanceId;

    /**
     * Constructor initializes the connection state
     */
    public function __construct()
    {
        // Generate a unique instance ID for tracking
        $this->instanceId = TypeHelper::guid();
        
        // Generate a pool key based on connection parameters from environment
        $this->poolKey = $this->generatePoolKey();
    }
       
    /**
     * Get the current maximum connection age
     * 
     * @return int Maximum connection age in seconds
     */
    public static function getMaxConnectionAge(): int
    {
        return $_ENV['DB_CONNECTION_MAX_AGE'];
    }
    
    /**
     * Generate a pool key based on database connection parameters
     * 
     * @return string The connection pool key
     */
    private function generatePoolKey(): string
    {
        // Create a hash from the database connection parameters
        return md5(
            $_ENV['DB_HOST'] .
            $_ENV['DB_PORT'] .
            $_ENV['DB_NAME'] .
            $_ENV['DB_USER']
        );
    }

    /**
     * Get the minimum pool size from environment variables
     * 
     * @return int The minimum pool size
     */
    public static function getMinPoolSize(): int
    {
        return (int)$_ENV['MIN_DB_CONNECTION_POOL'];
    }

    /**
     * Get the maximum pool size from environment variables
     * 
     * @return int The maximum pool size
     */
    public static function getMaxPoolSize(): int
    {
        return (int)$_ENV['MAX_DB_CONNECTION_POOL'];
    }
    
    /**
     * Get the current size of the connection pool
     * 
     * @param string|null $key Specific pool key to check, or null for total
     * @return int Number of connections in the pool
     */
    public static function getPoolSize(?string $key = null): int
    {
        if ($key !== null) {
            return isset(self::$connectionPool[$key]) ? count(self::$connectionPool[$key]) : 0;
        }
        
        $total = 0;
        foreach (self::$connectionPool as $connections) {
            $total += count($connections);
        }
        return $total;
    }
    
    /**
     * Get the total number of active connections
     * 
     * @return int Number of active connections
     */
    public static function getTotalConnections(): int
    {
        return self::$totalConnections;
    }
    
    /**
     * Clean expired connections from the pool
     * 
     * @param string|null $key Specific pool key to clean, or null for all
     * @return int Number of connections removed
     */
    public static function cleanExpiredConnections(?string $key = null): int
    {
        $now = time();
        $removed = 0;
        
        if ($key !== null) {
            // Clean specific pool
            if (isset(self::$connectionPool[$key])) {
                $initialCount = count(self::$connectionPool[$key]);
                self::$connectionPool[$key] = array_filter(
                    self::$connectionPool[$key],
                    function($item) use ($now) {
                        return ($now - $item['created_at']) < self::getMaxConnectionAge();
                    }
                );
                $removed = $initialCount - count(self::$connectionPool[$key]);
                self::$totalConnections -= $removed;
            }
            return $removed;
        }
        
        // Clean all pools
        foreach (self::$connectionPool as $poolKey => $connections) {
            $initialCount = count(self::$connectionPool[$poolKey]);
            self::$connectionPool[$poolKey] = array_filter(
                self::$connectionPool[$poolKey],
                function($item) use ($now) {
                    return ($now - $item['created_at']) < self::getMaxConnectionAge();
                }
            );
            $poolRemoved = $initialCount - count(self::$connectionPool[$poolKey]);
            $removed += $poolRemoved;
            self::$totalConnections -= $poolRemoved;
        }
        
        return $removed;
    }
    
    /**
     * Clear all connections from the pool
     * 
     * @param string|null $key Specific pool key to clear, or null for all
     * @return void
     */
    public static function clearPool(?string $key = null): void
    {
        if ($key !== null) {
            // Reduce total count by the number of connections in this pool
            self::$totalConnections -= count(self::$connectionPool[$key] ?? []);
            self::$connectionPool[$key] = [];
            return;
        }
        
        // Reset all pools and total count
        self::$connectionPool = [];
        self::$totalConnections = 0;
    }

    /**
     * Check if the connection is active
     * 
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Get the last error message
     * 
     * @return null|string Error message or null if no error
     */
    public function getError(): null|string
    {
        return $this->error;
    }
    
    /**
     * Get a connection from the pool or create a new one
     * 
     * @return PDO|null PDO connection or null on failure
     */
    public function connect(): PDO|null
    {
        // Clean expired connections before getting one
        self::cleanExpiredConnections($this->poolKey);
        
        // Try to get a connection from the pool
        if (isset(self::$connectionPool[$this->poolKey]) && !empty(self::$connectionPool[$this->poolKey])) {
            $connectionData = array_pop(self::$connectionPool[$this->poolKey]);
            $this->connection = $connectionData['connection'];
            self::$totalConnections--;
            
            // Test if the connection is still alive
            try {
                // Test connection first
                if ($this->connection->isConnected()) {
                    $this->db = $this->connection->db();
                    $this->isConnected = true;
                    $this->error = null;
                    self::$totalConnections++;
                    return $this->db;
                }
            } catch (\PDOException $e) {
                $this->error = $e->getMessage();
                unset($this->connection);
                unset($this->db);
            }
        }
        
        // Create new connection
        $this->connection = new DBConnection();
        $this->db = $this->connection->connect();
        if ($this->db) {
            $this->isConnected = true;
            self::$totalConnections++;
        }
        return $this->db;
    }
    
    /**
     * Get the current database connection
     * 
     * @return PDO|null The current database connection or null if not connected
     */
    public function db(): PDO|null
    {
        return $this->db;
    }
    
    /**
     * Return the connection to the pool
     * 
     * @return bool True if the connection was returned to the pool, false otherwise
     */
    public function releaseConnection(): bool
    {
        if (!$this->isConnected || $this->connection === null) {
            return false;
        }
        
        if (!isset(self::$connectionPool[$this->poolKey])) {
            self::$connectionPool[$this->poolKey] = [];
        }
        
        $maxPoolSize = self::getMaxPoolSize();
        if (count(self::$connectionPool[$this->poolKey]) < $maxPoolSize) {
            try {
                $this->db->query('SELECT 1');
                self::$connectionPool[$this->poolKey][] = [
                    'connection' => $this->connection,
                    'created_at' => time()
                ];
                $this->db = null;
                $this->connection = null;
                $this->isConnected = false;
                return true;
            } catch (\PDOException $e) {
                $this->error = "Connection not healthy: " . $e->getMessage();
            }
        }
        
        $this->connection->disconnect();
        self::$totalConnections--;
        $this->db = null;
        $this->connection = null;
        $this->isConnected = false;
        return false;
    }
    
    /**
     * Get the instance ID
     * 
     * @return string The unique instance ID
     */
    public function getInstanceId(): string
    {
        return $this->instanceId;
    }
    
    /**
     * Get the pool key
     * 
     * @return string The pool key based on connection parameters
     */
    public function getPoolKey(): string
    {
        return $this->poolKey;
    }
    
    /**
     * Destructor that ensures connections are properly handled
     */
    public function __destruct()
    {
        // Return connection to the pool if still connected
        if ($this->isConnected && $this->db !== null) {
            $this->releaseConnection();
        }
    }
}
