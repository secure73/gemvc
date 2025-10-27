<?php

namespace Gemvc\Database;

use PDO;

/**
 * Unified Database Manager Interface
 * 
 * This interface provides a consistent API for database connection management
 * across different web server environments:
 * - Apache PHP-FPM
 * - Nginx PHP-FPM  
 * - OpenSwoole
 * 
 * Each implementation handles connection pooling appropriate to its environment.
 */
interface DatabaseManagerInterface
{
    /**
     * Get a database connection
     * 
     * @param string $poolName Connection pool name (default: 'default')
     * @return \PDO|null Active PDO connection or null on error
     */
    public function getConnection(string $poolName = 'default'): ?\PDO;

    /**
     * Release a connection back to the pool
     * 
     * @param \PDO $connection The connection to release
     * @return void
     */
    public function releaseConnection(\PDO $connection): void;

    /**
     * Get the last error message
     * 
     * @return string|null Error message or null if no error occurred
     */
    public function getError(): ?string;

    /**
     * Set an error message
     * 
     * @param string|null $error The error message to set
     * @param array<string, mixed> $context Additional context information
     * @return void
     */
    public function setError(?string $error, array $context = []): void;

    /**
     * Clear the last error message
     * 
     * @return void
     */
    public function clearError(): void;

    /**
     * Check if the database manager is properly initialized
     * 
     * @return bool True if initialized, false otherwise
     */
    public function isInitialized(): bool;

    /**
     * Get connection pool statistics
     * 
     * @return array<string, mixed> Pool statistics
     */
    public function getPoolStats(): array;

    /**
     * Begin a database transaction
     * 
     * @param string $poolName Connection pool name
     * @return bool True on success, false on failure
     */
    public function beginTransaction(string $poolName = 'default'): bool;

    /**
     * Commit the current transaction
     * 
     * @param string $poolName Connection pool name
     * @return bool True on success, false on failure
     */
    public function commit(string $poolName = 'default'): bool;

    /**
     * Rollback the current transaction
     * 
     * @param string $poolName Connection pool name
     * @return bool True on success, false on failure
     */
    public function rollback(string $poolName = 'default'): bool;

    /**
     * Check if currently in a transaction
     * 
     * @param string $poolName Connection pool name
     * @return bool True if in transaction, false otherwise
     */
    public function inTransaction(string $poolName = 'default'): bool;
}
