<?php

namespace Gemvc\Database;

use PDO;
use PDOException;
use Gemvc\Helper\ProjectHelper;

/**
 * Simple PDO Database Manager for Apache/Nginx PHP-FPM
 * 
 * This implementation provides basic PDO connection management
 * suitable for traditional web server environments where each
 * request gets its own PHP process.
 * 
 * Features:
 * - Simple PDO connection per request
 * - Basic error handling
 * - Transaction support
 * - Environment-based configuration
 */
class SimplePdoDatabaseManager implements DatabaseManagerInterface
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

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->initialize();
    }

    /**
     * Get the singleton instance
     * 
     * @return self The singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
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
                error_log("SimplePdoDatabaseManager: Initialized for Apache/Nginx environment");
            }
        } catch (\Exception $e) {
            $this->setError('Failed to initialize SimplePdoDatabaseManager: ' . $e->getMessage());
            $this->initialized = false;
        }
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

        // Return existing connection if available
        if ($this->currentConnection !== null) {
            return $this->currentConnection;
        }

        try {
            $this->currentConnection = $this->createConnection();
            
            if (($_ENV['APP_ENV'] ?? '') === 'dev') {
                error_log("SimplePdoDatabaseManager: New PDO connection created");
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
        // In simple PDO implementation, we just disconnect
        if ($this->currentConnection === $connection) {
            $this->disconnect();
        }
    }

    /**
     * Create a new PDO connection
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
            PDO::ATTR_PERSISTENT => false, // No persistent connections in simple implementation
        ];

        return new PDO(
            $dsn,
            is_string($this->config['username']) ? $this->config['username'] : 'root',
            is_string($this->config['password']) ? $this->config['password'] : '',
            $options
        );
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
                error_log("SimplePdoDatabaseManager: Connection disconnected");
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
            'type' => 'Simple PDO',
            'environment' => 'Apache/Nginx PHP-FPM',
            'has_connection' => $this->currentConnection !== null,
            'in_transaction' => $this->inTransaction,
            'initialized' => $this->initialized,
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
     * Clean up resources on destruction
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
