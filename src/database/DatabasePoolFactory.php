<?php
namespace Gemvc\Database;

/**
 * Factory class for creating database connection pool instances
 */
class DatabasePoolFactory {
    private static ?AbstractDatabasePool $instance = null;

    /**
     * Get the appropriate database connection pool instance based on the environment
     * 
     * @return AbstractDatabasePool
     * @throws \RuntimeException if neither OpenSwoole nor standard environment is available
     */
    public static function getInstance(): AbstractDatabasePool {
        if (self::$instance === null) {
            if (extension_loaded('openswoole')) {
                self::$instance = OpenSwooleDatabasePool::getInstance();
            } else {
                self::$instance = StandardDatabasePool::getInstance();
            }
        }
        return self::$instance;
    }

    /**
     * Reset the singleton instance
     * Useful for testing or when you need to force a new connection
     */
    public static function resetInstance(): void {
        if (self::$instance !== null) {
            // Reset the underlying pool instance
            if (extension_loaded('openswoole')) {
                OpenSwooleDatabasePool::resetInstance();
            } else {
                StandardDatabasePool::resetInstance();
            }
            self::$instance = null;
        }
    }

    /**
     * Get the current pool status
     * 
     * @return array
     */
    public static function getPoolStatus(): array {
        return self::getInstance()->getPoolStatus();
    }

    /**
     * Get the last error message from the pool
     * 
     * @return string|null
     */
    public static function getLastError(): ?string {
        return self::getInstance()->getError();
    }
} 