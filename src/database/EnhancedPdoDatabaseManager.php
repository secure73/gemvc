<?php

namespace Gemvc\Database;

use PDO;
use PDOException;
use Gemvc\Helper\ProjectHelper;

/**
 * Enhanced PDO Database Manager with Optional Persistent Connections
 * 
 * This implementation provides PDO connection management with the option
 * to use persistent connections for high-traffic scenarios.
 */
class EnhancedPdoDatabaseManager implements DatabaseManagerInterface
{
    /** @var self|null Singleton instance */
    private static ?self $instance = null;

    /** @var PDO|null Current PDO connection */
    private ?PDO $currentConnection = null;

    /** @var string|null Last error message */
    private ?string $error = null;

    /** @var bool Whether the manager is initialized */
    private bool $initialized = false;

    /** @var array<string, mixed> Connection configuration */
    private array $config = [];

    /** @var bool Whether currently in a transaction */
    private bool $inTransaction = false;

    /** @var bool Whether to use persistent connections */
    private bool $usePersistentConnections = false;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct(bool $usePersistentConnections = false)
    {
        $this->usePersistentConnections = $usePersistentConnections;
        $this->initialize();
    }

    /**
     * Get the singleton instance
     * 
     * @param bool $usePersistentConnections Whether to use persistent connections
     * @return self The singleton instance
     */
    public static function getInstance(bool $usePersistentConnections = false): self
    {
        if (self::$instance === null) {
            self::$instance = new self($usePersistentConnections);
        }
        return self::$instance;
    }

    /**
     * Initialize the database manager
     * 
     * @return void
     */
    private function initialize(): void
    {
        try {
            // Load environment variables
            ProjectHelper::loadEnv();

            // Build database configuration
            $this->config = $this->buildDatabaseConfig();
            
            $this->initialized = true;
            
            if (($_ENV['APP_ENV'] ?? '') === 'dev') {
                $connectionType = $this->usePersistentConnections ? 'persistent' : 'simple';
                error_log("EnhancedPdoDatabaseManager: Initialized with {$connectionType} connections");
            }
        } catch (\Exception $e) {
            $this->setError('Failed to initialize EnhancedPdoDatabaseManager: ' . $e->getMessage());
            $this->initialized = false;
        }
    }

    /**
     * Create a new PDO connection with optional persistence
     * 
     * @return PDO The new PDO connection
     * @throws PDOException If connection fails
     */
    private function createConnection(): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            is_string($this->config['driver']) ? $this->config['driver'] : 'mysql',
            is_string($this->config['host']) ? $this->config['host'] : 'localhost',
            is_numeric($this->config['port']) ? (string)$this->config['port'] : '3306',
            is_string($this->config['database']) ? $this->config['database'] : 'gemvc_db',
            is_string($this->config['charset']) ? $this->config['charset'] : 'utf8mb4'
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_PERSISTENT => $this->usePersistentConnections, // ← Configurable persistence
        ];

        // Add persistent connection specific options
        if ($this->usePersistentConnections) {
            $options[PDO::ATTR_PERSISTENT] = true;
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'";
        }

        return new PDO(
            $dsn,
            is_string($this->config['username']) ? $this->config['username'] : 'root',
            is_string($this->config['password']) ? $this->config['password'] : '',
            $options
        );
    }

    /**
     * Get a database connection
     * 
     * @param string $poolName Connection pool name (ignored in simple implementation)
     * @return \PDO|null Active PDO connection or null on error
     */
    public function getConnection(string $poolName = 'default'): ?\PDO
    {
        $this->clearError();

        // For persistent connections, always create new (they're managed by PHP)
        if ($this->usePersistentConnections) {
            try {
                $this->currentConnection = $this->createConnection();
                
                if (($_ENV['APP_ENV'] ?? '') === 'dev') {
                    error_log("EnhancedPdoDatabaseManager: New persistent PDO connection created");
                }
                
                return $this->currentConnection;
            } catch (PDOException $e) {
                $this->setError('Failed to create persistent database connection: ' . $e->getMessage(), [
                    'error_code' => $e->getCode(),
                    'pool_name' => $poolName
                ]);
                return null;
            }
        }

        // For non-persistent connections, reuse existing
        if ($this->currentConnection !== null) {
            return $this->currentConnection;
        }

        try {
            $this->currentConnection = $this->createConnection();
            
            if (($_ENV['APP_ENV'] ?? '') === 'dev') {
                error_log("EnhancedPdoDatabaseManager: New simple PDO connection created");
            }
            
            return $this->currentConnection;
        } catch (PDOException $e) {
            $this->setError('Failed to create database connection: ' . $e->getMessage(), [
                'error_code' => $e->getCode(),
                'pool_name' => $poolName
            ]);
            return null;
        }
    }

    /**
     * Release a connection back to the pool
     * 
     * @param \PDO $connection The connection to release
     * @return void
     */
    public function releaseConnection(\PDO $connection): void
    {
        if ($this->usePersistentConnections) {
            // For persistent connections, PHP manages the lifecycle
            // We just clear our reference
            if ($this->currentConnection === $connection) {
                $this->currentConnection = null;
            }
        } else {
            // For simple connections, disconnect
            if ($this->currentConnection === $connection) {
                $this->disconnect();
            }
        }
    }

    /**
     * Build database configuration from environment variables
     * 
     * @return array<string, mixed> Database configuration
     */
    private function buildDatabaseConfig(): array
    {
        return [
            'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => is_numeric($_ENV['DB_PORT'] ?? null) ? (int)($_ENV['DB_PORT']) : 3306,
            'database' => $_ENV['DB_NAME'] ?? 'gemvc_db',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        ];
    }

    /**
     * Disconnect from the database
     * 
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->currentConnection !== null) {
            // Rollback any pending transaction
            if ($this->inTransaction) {
                try {
                    $this->currentConnection->rollBack();
                } catch (PDOException $e) {
                    error_log('Error during rollback on disconnect: ' . $e->getMessage());
                }
                $this->inTransaction = false;
            }
            
            $this->currentConnection = null;
            
            if (($_ENV['APP_ENV'] ?? '') === 'dev') {
                $connectionType = $this->usePersistentConnections ? 'persistent' : 'simple';
                error_log("EnhancedPdoDatabaseManager: {$connectionType} connection disconnected");
            }
        }
    }

    /**
     * Get the last error message
     * 
     * @return string|null Error message or null if no error occurred
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Set an error message
     * 
     * @param string|null $error The error message to set
     * @param array<string, mixed> $context Additional context information
     * @return void
     */
    public function setError(?string $error, array $context = []): void
    {
        if ($error === null) {
            $this->error = null;
            return;
        }

        // Add context information to error message
        if (!empty($context)) {
            $contextStr = ' [Context: ' . json_encode($context) . ']';
            $this->error = $error . $contextStr;
        } else {
            $this->error = $error;
        }
    }

    /**
     * Clear the last error message
     * 
     * @return void
     */
    public function clearError(): void
    {
        $this->error = null;
    }

    /**
     * Check if the database manager is properly initialized
     * 
     * @return bool True if initialized, false otherwise
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get connection pool statistics
     * 
     * @return array<string, mixed> Pool statistics
     */
    public function getPoolStats(): array
    {
        return [
            'type' => $this->usePersistentConnections ? 'Enhanced PDO (Persistent)' : 'Enhanced PDO (Simple)',
            'environment' => 'Apache/Nginx PHP-FPM',
            'has_connection' => $this->currentConnection !== null,
            'in_transaction' => $this->inTransaction,
            'initialized' => $this->initialized,
            'persistent' => $this->usePersistentConnections,
            'config' => [
                'driver' => $this->config['driver'] ?? 'unknown',
                'host' => $this->config['host'] ?? 'unknown',
                'database' => $this->config['database'] ?? 'unknown',
            ]
        ];
    }

    /**
     * Begin a database transaction
     * 
     * @param string $poolName Connection pool name (ignored)
     * @return bool True on success, false on failure
     */
    public function beginTransaction(string $poolName = 'default'): bool
    {
        if ($this->inTransaction) {
            $this->setError('Already in transaction');
            return false;
        }

        $connection = $this->getConnection();
        if ($connection === null) {
            return false;
        }

        try {
            $result = $connection->beginTransaction();
            $this->inTransaction = $result;
            return $result;
        } catch (PDOException $e) {
            $this->setError('Failed to begin transaction: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Commit the current transaction
     * 
     * @param string $poolName Connection pool name (ignored)
     * @return bool True on success, false on failure
     */
    public function commit(string $poolName = 'default'): bool
    {
        if (!$this->inTransaction || $this->currentConnection === null) {
            $this->setError('No active transaction to commit');
            return false;
        }

        try {
            $result = $this->currentConnection->commit();
            $this->inTransaction = false;
            return $result;
        } catch (PDOException $e) {
            $this->setError('Failed to commit transaction: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rollback the current transaction
     * 
     * @param string $poolName Connection pool name (ignored)
     * @return bool True on success, false on failure
     */
    public function rollback(string $poolName = 'default'): bool
    {
        if (!$this->inTransaction || $this->currentConnection === null) {
            $this->setError('No active transaction to rollback');
            return false;
        }

        try {
            $result = $this->currentConnection->rollBack();
            $this->inTransaction = false;
            return $result;
        } catch (PDOException $e) {
            $this->setError('Failed to rollback transaction: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if currently in a transaction
     * 
     * @param string $poolName Connection pool name (ignored)
     * @return bool True if in transaction, false otherwise
     */
    public function inTransaction(string $poolName = 'default'): bool
    {
        return $this->inTransaction;
    }

    /**
     * Reset the singleton instance (useful for testing)
     * 
     * @return void
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            self::$instance->disconnect();
            self::$instance = null;
        }
    }

    /**
     * Clean up resources on destruction
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
