<?php
namespace Gemvc\Database;

use PDO;
use PDOException;
use OpenSwoole\Coroutine\Channel;

/**
 * OpenSwoole-specific database connection pool manager
 */
class OpenSwooleDatabasePool extends AbstractDatabasePool {
    private Channel $pool;

    protected function __construct() {
        if (!extension_loaded('openswoole')) {
            throw new \RuntimeException('OpenSwoole extension is not loaded');
        }
        parent::__construct();
    }

    protected function initializePool(): void {
        try {
            $this->pool = new Channel($this->maxPoolSize);
            for ($i = 0; $i < $this->initialPoolSize; $i++) {
                $this->pool->push($this->createConnection());
            }
            $this->isInitialized = true;
            $this->warmupPool();
            $this->log("OpenSwoole database pool initialized with {$this->initialPoolSize} connections.");
        } catch (PDOException $e) {
            $this->error = "Failed to initialize OpenSwoole pool: " . $e->getMessage();
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

        try {
            $connection = $this->pool->pop(0.1); // 100ms timeout
            if ($connection && $this->validateConnection($connection)) {
                $this->trackConnection($connection);
                $this->updateMetrics($startTime);
                return $connection;
            }
        } catch (\Throwable $e) {
            $this->error = "Failed to get connection from OpenSwoole pool: " . $e->getMessage();
            $this->log($this->error);
            $this->metrics['failed_connections']++;
        }

        // If we couldn't get a connection from the pool, create a new one
        $connection = $this->createConnection();
        $this->trackConnection($connection);
        $this->updateMetrics($startTime);
        return $connection;
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

        try {
            $this->pool->push($connection);
        } catch (\Throwable $e) {
            $this->log("OpenSwoole release error: " . $e->getMessage());
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
            $this->error = "OpenSwoole connection validation failed: " . $e->getMessage();
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
        while ($this->pool->length() > 0) {
            $this->pool->pop();
        }

        $this->currentConnection = null;
        $this->connectionStats = [];
    }

    protected function createConnection(): PDO {
        $pdo = parent::createConnection();
        $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
        return $pdo;
    }

    public function getPoolStatus(): array {
        return [
            'max_size' => $this->maxPoolSize,
            'current_size' => $this->pool->length(),
            'is_initialized' => $this->isInitialized,
            'has_error' => $this->error !== null,
            'last_error' => $this->error,
            'active_connections' => count($this->activeConnections)
        ];
    }

    protected function warmupPool(): void {
        while ($this->pool->length() < $this->initialPoolSize) {
            $this->pool->push($this->createConnection());
        }
    }
} 