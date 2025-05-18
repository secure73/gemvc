<?php

namespace Gemvc\Database;

use PDO;
use PDOException;

class DBPoolManager
{
    /**
     * Pool of available connections
     * @var array<string, array{connection: DBConnection, created_at: int}>
     */
    private static array $availableConnections = [];

    /**
     * Connections currently in use
     * @var array<string, array{connection: DBConnection, started_at: int}>
     */
    private static array $inuseConnections = [];

    private static int $lastCleanupTime = 0;
    private const CLEANUP_INTERVAL_SECONDS = 60;

    public function __construct()
    {
        // Public instantiation allowed
    }

    public static function getInstance(): self
    {
        static $singleton = null;
        if (!$singleton) {
            $singleton = new self();
        }
        return $singleton;
    }

    /**
     * Get a healthy PDO connection from the pool
     * 
     * @throws PDOException if no healthy connection is available
     */
    public function getConnection(): PDO
    {
        $pdo = $this->getHealthyConnectionFromPool();
        if ($pdo !== null) {
            return $pdo;
        }

        if (time() - self::$lastCleanupTime > self::CLEANUP_INTERVAL_SECONDS) {
            $this->cleanExpiredConnections();
            self::$lastCleanupTime = time();

            $pdo = $this->getHealthyConnectionFromPool();
            if ($pdo !== null) {
                return $pdo;
            }
        }

        // Attempt to create a new connection if under max pool size
        if ($this->getTotalConnectionsCount() < $this->getMaxPoolSize()) {
            $conn = new DBConnection();
            $pdo = $conn->connect();
            if (!$pdo) {
                $conn->disconnect();
                throw new PDOException("Failed to create new DB connection");
            }

            $id = $conn->getInstanceId();
            self::$inuseConnections[$id] = [
                'connection' => $conn,
                'started_at' => time()
            ];

            return $pdo;
        }

        throw new PDOException("DBPool exhausted: " . $this->getTotalConnectionsCount() . " connections in use.");
    }

    /**
     * Attempt to get and validate one connection from available pool
     */
    private function getHealthyConnectionFromPool(): ?PDO
    {
        while (!empty(self::$availableConnections)) {
            $connectionData = array_pop(self::$availableConnections);
            $connection = $connectionData['connection'];

            if (!$connection->isConnected()) {
                continue;
            }

            try {
                $pdo = $connection->db();
                $pdo->query('SELECT 1'); // Health check

                $instanceId = $connection->getInstanceId();
                self::$inuseConnections[$instanceId] = [
                    'connection' => $connection,
                    'started_at' => time()
                ];

                return $pdo;
            } catch (PDOException) {
                $connection->disconnect();
            }
        }

        return null;
    }

    /**
     * Release a PDO connection back to the pool
     */
    public function release(PDO $pdo): bool
    {
        foreach (self::$inuseConnections as $id => $data) {
            if ($data['connection']->db() === $pdo) {
                $this->releaseConnection($data['connection']);
                return true;
            }
        }

        error_log("Warning: Attempted to release an unknown or already released PDO instance.");
        return false;
    }

    /**
     * Actually release and recycle a connection
     */
    private function releaseConnection(DBConnection $connection): void
    {
        $instanceId = $connection->getInstanceId();
        unset(self::$inuseConnections[$instanceId]);

        if (!$connection->isConnected()) {
            $connection->disconnect();
            return;
        }

        if (count(self::$availableConnections) >= $this->getMaxPoolSize()) {
            $connection->disconnect();
            return;
        }

        try {
            $connection->db()->query('SELECT 1'); // Health check
            self::$availableConnections[$instanceId] = [
                'connection' => $connection,
                'created_at' => time()
            ];
        } catch (PDOException) {
            $connection->disconnect();
        }
    }

    /**
     * Remove stale connections
     */
    private function cleanExpiredConnections(): void
    {
        $now = time();
        $maxAge = $this->getMaxConnectionAge();

        // Clean available pool
        self::$availableConnections = array_filter(
            self::$availableConnections,
            fn($item) => ($now - $item['created_at']) < $maxAge
        );

        // Clean in-use pool
        foreach (self::$inuseConnections as $id => $data) {
            if (($now - $data['started_at']) > $maxAge) {
                $data['connection']->disconnect();
                unset(self::$inuseConnections[$id]);
            }
        }
    }

    /**
     * Manual cleanup (for long-lived apps like Swoole)
     */
    public function shutdown(): void
    {
        foreach (self::$availableConnections as $data) {
            $data['connection']->disconnect();
        }
        self::$availableConnections = [];

        foreach (self::$inuseConnections as $data) {
            $data['connection']->disconnect();
        }
        self::$inuseConnections = [];
    }

    /**
     * Get connection pool status (for debugging)
     */
    public function getPoolStatus(): array
    {
        return [
            'available' => $this->getAvailableConnectionsCount(),
            'in_use' => $this->getInuseConnectionsCount(),
            'total' => $this->getTotalConnectionsCount(),
            'last_cleanup' => self::$lastCleanupTime
        ];
    }

    /**
     * Get detailed connection pool status including configuration values
     * This is an extension of getPoolStatus() with additional metrics
     * keys: 'max_age', 'max_pool_size', 'cleanup_interval'
     * @return array<string, mixed>
     */
    public function getDetailedPoolStatus(): array
    {
        return array_merge($this->getPoolStatus(), [
            'max_age' => $this->getMaxConnectionAge(),
            'max_pool_size' => $this->getMaxPoolSize(),
            'cleanup_interval' => self::CLEANUP_INTERVAL_SECONDS
        ]);
    }

    public function getAvailableConnectionsCount(): int
    {
        return count(self::$availableConnections);
    }

    public function getInuseConnectionsCount(): int
    {
        return count(self::$inuseConnections);
    }

    public function getTotalConnectionsCount(): int
    {
        return $this->getAvailableConnectionsCount() + $this->getInuseConnectionsCount();
    }

    public function __destruct()
    {
        $this->shutdown();
    }

    private function getMaxConnectionAge(): int
    {
        return (int) ($_ENV['DB_CONNECTION_MAX_AGE'] ?? 300); // 5 minutes default
    }

    private function getMaxPoolSize(): int
    {
        return (int) ($_ENV['MAX_DB_CONNECTION_POOL'] ?? 10); // 10 connections default
    }
}
