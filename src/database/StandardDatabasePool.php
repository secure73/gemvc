<?php
namespace Gemvc\Database;

use PDO;
use PDOException;

/**
 * Standard database connection pool manager for Apache/Nginx environments
 */
class StandardDatabasePool extends AbstractDatabasePool {
    private array $pool = [];

    protected function __construct() {
        parent::__construct();
    }

    protected function initializePool(): void {
        try {
            for ($i = 0; $i < $this->initialPoolSize; $i++) {
                $this->pool[] = $this->createConnection();
            }
            $this->isInitialized = true;
            $this->log("Standard database pool initialized with {$this->initialPoolSize} connections.");
        } catch (PDOException $e) {
            $this->error = "Failed to initialize standard pool: " . $e->getMessage();
            $this->log($this->error);
            throw $e;
        }
    }

    public function getConnection(): PDO {
        if ($this->isCircuitBreakerOpen()) {
            throw new \RuntimeException('Circuit breaker is open - too many failed connections');
        }

        $startTime = microtime(true);
        $this->metrics['connection_attempts']++;

        if (!$this->isInitialized) {
            $this->initializePool();
        }

        // Try to get a connection from the pool
        while (!empty($this->pool)) {
            $connection = array_pop($this->pool);
            if ($this->validateConnection($connection)) {
                $this->trackConnection($connection);
                $this->updateMetrics($startTime);
                return $connection;
            }
        }

        // If no valid connection in pool, create a new one
        try {
            $connection = $this->createConnection();
            $this->trackConnection($connection);
            $this->updateMetrics($startTime);
            return $connection;
        } catch (\Throwable $e) {
            $this->metrics['failed_connections']++;
            throw $e;
        }
    }

    public function releaseConnection(PDO $connection): void {
        $connectionId = spl_object_hash($connection);
        
        if (isset($this->activeConnections[$connectionId])) {
            unset($this->activeConnections[$connectionId]);
        }

        if (!$this->validateConnection($connection)) {
            $this->log("Unhealthy connection discarded");
            return;
        }

        // Only add back to pool if we haven't reached max size
        if (count($this->pool) < $this->maxPoolSize) {
            $this->pool[] = $connection;
        }

        $this->currentConnection = null;
    }

    protected function validateConnection(PDO $connection): bool {
        if (!$this->checkConnectionAge($connection)) {
            return false;
        }

        try {
            $connection->setAttribute(PDO::ATTR_TIMEOUT, 2);
            $connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            $this->error = "Standard connection validation failed: " . $e->getMessage();
            return false;
        }
    }

    protected function cleanupAllConnections(): void {
        // Cleanup active connections
        foreach ($this->activeConnections as $id => $data) {
            $data['connection'] = null;
        }
        $this->activeConnections = [];

        // Cleanup pool
        foreach ($this->pool as $connection) {
            $connection = null;
        }
        $this->pool = [];

        $this->currentConnection = null;
        $this->connectionStats = [];
    }

    protected function createConnection(): PDO {
        return parent::createConnection();
    }

    public function getPoolStatus(): array {
        return [
            'max_size' => $this->maxPoolSize,
            'current_size' => count($this->pool),
            'is_initialized' => $this->isInitialized,
            'has_error' => $this->error !== null,
            'last_error' => $this->error,
            'active_connections' => count($this->activeConnections)
        ];
    }
} 