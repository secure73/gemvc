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
    
    /**
     * Static connection pool to store and reuse database connections
     * Keyed by connection parameters hash
     * Each connection is stored with its creation timestamp
     * @var array<string, array<int, array{connection: PDO, created_at: int}>>
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
        
        // Try to get a connection from the pool for this connection parameter set
        if (isset(self::$connectionPool[$this->poolKey]) && !empty(self::$connectionPool[$this->poolKey])) {
            $connectionData = array_pop(self::$connectionPool[$this->poolKey]);
            $this->db = $connectionData['connection'];
            self::$totalConnections--; // Reduce count as it's taken from the pool
            
            // Test if the connection is still alive before returning it
            try {
                $this->db->query('SELECT 1');
                $this->isConnected = true;
                $this->error = null;
                self::$totalConnections++; // Increment count for this active connection
                return $this->db;
            } catch (\PDOException $e) {
                // Connection is stale, create a new one
                unset($this->db);
            }
        }
        
        // Create a new connection
        return $this->createNewConnection();
    }
    
    /**
     * Create a new database connection using environment variables
     * 
     * @return PDO|null PDO connection or null on failure
     */
    private function createNewConnection(): PDO|null
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME'],
            $_ENV['DB_CHARSET']
        );
        
        try {
            $options = [
                \PDO::ATTR_PERSISTENT => true, // Always use persistent connections
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ];
            
            $this->db = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $options);
            $this->isConnected = true;
            $this->error = null;
            self::$totalConnections++; // Increment total count
            return $this->db;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();   
        }
        return null;
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
        if (!$this->isConnected || $this->db === null) {
            return false;
        }
        
        // Check if the pool for this key exists, create if not
        if (!isset(self::$connectionPool[$this->poolKey])) {
            self::$connectionPool[$this->poolKey] = [];
        }
        
        // Only add connection to the pool if we haven't reached the max size
        $maxPoolSize = self::getMaxPoolSize();
        if (count(self::$connectionPool[$this->poolKey]) < $maxPoolSize) {
            // Test the connection before adding it back to the pool
            try {
                $this->db->query('SELECT 1');
                // Store connection with current timestamp
                self::$connectionPool[$this->poolKey][] = [
                    'connection' => $this->db,
                    'created_at' => time()
                ];
                $this->db = null;
                $this->isConnected = false;
                // Note: don't decrement totalConnections here as it's still in the pool
                return true;
            } catch (\PDOException $e) {
                // Connection is not healthy, don't add to pool
                $this->error = "Connection not healthy: " . $e->getMessage();
            }
        }
        
        // Connection was not added to pool, reduce count
        self::$totalConnections--;
        $this->db = null;
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
