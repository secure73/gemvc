<?php
namespace Gemvc\Database;

use PDO;

/**
 * Abstract base class for database connection pool management
 */
abstract class AbstractDatabasePool {
    protected static ?self $instance = null;
    protected ?PDO $currentConnection = null;
    protected int $maxPoolSize;
    protected int $maxConnectionAge;
    protected bool $debugMode;
    protected int $initialPoolSize;
    protected ?string $error = null;
    protected bool $isInitialized = false;
    protected int $reinitializeAttempts = 0;

    // Connection tracking
    protected array $activeConnections = [];
    protected array $connectionStats = [];
    protected array $queryStats = [];

    // Performance metrics
    protected array $metrics = [
        'total_connections' => 0,
        'failed_connections' => 0,
        'average_connection_time' => 0,
        'total_connection_time' => 0,
        'connection_attempts' => 0,
        'query_count' => 0,
        'failed_queries' => 0,
        'average_query_time' => 0
    ];

    protected const MAX_REINITIALIZE_ATTEMPTS = 3;
    protected const CONNECTION_TIMEOUT = 5;
    protected const REINITIALIZE_BACKOFF_MS = 1000;
    protected const MAX_CONNECTION_LIFETIME = 3600; // 1 hour
    protected const CONNECTION_CHECK_INTERVAL = 300; // 5 minutes
    protected const CIRCUIT_BREAKER_THRESHOLD = 5;
    protected const CIRCUIT_BREAKER_TIMEOUT = 30; // seconds
    protected const QUERY_TIMEOUT = 30; // seconds
    protected const MAX_RETRY_ATTEMPTS = 3;

    protected function __construct() {
        $this->maxPoolSize = (int) ($_ENV['MAX_DB_CONNECTION_POOL'] ?? 10);
        $this->maxConnectionAge = (int) ($_ENV['DB_CONNECTION_MAX_AGE'] ?? 300);
        $this->initialPoolSize = (int) ($_ENV['INITIAL_DB_CONNECTION_POOL'] ?? 3);
        $this->debugMode = ($_ENV['APP_ENV'] ?? 'prod') === 'dev';

        $this->validateConfiguration();
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance(): self {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Reset the singleton instance (useful for testing)
     */
    public static function resetInstance(): void {
        if (static::$instance !== null) {
            static::$instance->cleanupAllConnections();
            static::$instance = null;
        }
    }

    /**
     * Validate configuration values
     */
    protected function validateConfiguration(): void {
        if ($this->maxPoolSize < 1) {
            throw new \InvalidArgumentException("MAX_DB_CONNECTION_POOL must be >= 1");
        }
        if ($this->maxConnectionAge < 60) {
            throw new \InvalidArgumentException("DB_CONNECTION_MAX_AGE must be >= 60 seconds");
        }
        if ($this->initialPoolSize < 1 || $this->initialPoolSize > $this->maxPoolSize) {
            throw new \InvalidArgumentException("INITIAL_DB_CONNECTION_POOL must be between 1 and MAX_DB_CONNECTION_POOL");
        }
    }

    /**
     * Get the last error message
     */
    public function getError(): ?string {
        return $this->error;
    }

    /**
     * Clear the last error message
     */
    protected function clearError(): void {
        $this->error = null;
    }

    /**
     * Create a new database connection
     */
    protected function createConnection(): PDO {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME'],
            $_ENV['DB_CHARSET']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => self::CONNECTION_TIMEOUT
        ];
        
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $options);
        $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES " . $_ENV['DB_CHARSET']);
        $this->log("New database connection created");
        
        return $pdo;
    }

    /**
     * Log a message if in debug mode
     */
    protected function log(string $message): void {
        if ($this->debugMode) {
            error_log($message);
        }
    }

    /**
     * Track an active connection
     */
    protected function trackConnection(PDO $connection): void {
        $connectionId = spl_object_hash($connection);
        $this->activeConnections[$connectionId] = [
            'connection' => $connection,
            'created_at' => time(),
            'last_used' => time()
        ];
    }

    /**
     * Update connection metrics
     */
    protected function updateMetrics(float $startTime): void {
        $connectionTime = microtime(true) - $startTime;
        $this->metrics['total_connection_time'] += $connectionTime;
        $this->metrics['total_connections']++;
        $this->metrics['average_connection_time'] = 
            $this->metrics['total_connection_time'] / $this->metrics['total_connections'];
    }

    /**
     * Check connection age
     */
    protected function checkConnectionAge(PDO $connection): bool {
        $connectionId = spl_object_hash($connection);
        if (isset($this->activeConnections[$connectionId])) {
            $age = time() - $this->activeConnections[$connectionId]['created_at'];
            return $age < $this->maxConnectionAge;
        }
        return true;
    }

    /**
     * Check if the circuit breaker is open
     */
    protected function isCircuitBreakerOpen(): bool {
        return $this->metrics['failed_connections'] > self::CIRCUIT_BREAKER_THRESHOLD;
    }

    /**
     * Update query metrics
     */
    protected function updateQueryMetrics(float $startTime, bool $success): void {
        $queryTime = microtime(true) - $startTime;
        $this->metrics['query_count']++;
        if (!$success) {
            $this->metrics['failed_queries']++;
        }
        $this->metrics['average_query_time'] = 
            ($this->metrics['average_query_time'] * ($this->metrics['query_count'] - 1) + $queryTime) 
            / $this->metrics['query_count'];
    }

    /**
     * Abstract methods that must be implemented by concrete classes
     */
    abstract public function getConnection(): PDO;
    abstract public function releaseConnection(PDO $connection): void;
    abstract protected function initializePool(): void;
    abstract protected function cleanupAllConnections(): void;
    abstract protected function validateConnection(PDO $connection): bool;
} 