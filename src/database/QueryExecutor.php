<?php
namespace Gemvc\Database;

use PDO;
use PDOException;
use PDOStatement;

class QueryExecutor {
    private DatabaseConnection $connection;
    private ?PDOStatement $statement = null;
    private bool $inTransaction = false;
    private float $startTime;
    private bool $debugMode;
    
    public function __construct(DatabaseConnection $connection) {
        $this->connection = $connection;
        $this->startTime = microtime(true);
        $this->debugMode = ($_ENV['APP_ENV'] ?? '') === 'dev';
    }
    
    public function query(string $sql, array $params = []): array {
        $connection = $this->connection->getConnection();
        try {
            $this->statement = $connection->prepare($sql);
            $this->statement->execute($params);
            return $this->statement->fetchAll();
        } finally {
            $this->connection->releaseConnection($connection);
            $this->statement = null;
        }
    }
    
    public function queryObjects(string $sql, array $params = []): array {
        $connection = $this->connection->getConnection();
        try {
            $this->statement = $connection->prepare($sql);
            $this->statement->execute($params);
            return $this->statement->fetchAll(PDO::FETCH_OBJ);
        } finally {
            $this->connection->releaseConnection($connection);
            $this->statement = null;
        }
    }
    
    public function queryOne(string $sql, array $params = []): ?array {
        $connection = $this->connection->getConnection();
        try {
            $this->statement = $connection->prepare($sql);
            $this->statement->execute($params);
            $result = $this->statement->fetch();
            return $result ?: null;
        } finally {
            $this->connection->releaseConnection($connection);
            $this->statement = null;
        }
    }
    
    public function execute(string $sql, array $params = []): int {
        $connection = $this->connection->getConnection();
        try {
            $this->statement = $connection->prepare($sql);
            $this->statement->execute($params);
            return $this->statement->rowCount();
        } finally {
            $this->connection->releaseConnection($connection);
            $this->statement = null;
        }
    }
    
    public function insert(string $table, array $data): int {
        $fields = array_keys($data);
        $placeholders = array_map(fn($field) => ":$field", $fields);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $connection = $this->connection->getConnection();
        try {
            $this->statement = $connection->prepare($sql);
            $this->statement->execute($data);
            return (int)$connection->lastInsertId();
        } finally {
            $this->connection->releaseConnection($connection);
            $this->statement = null;
        }
    }
    
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setClause = implode(', ', array_map(
            fn($field) => "$field = :$field",
            array_keys($data)
        ));
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            $setClause,
            $where
        );
        
        $params = array_merge($data, $whereParams);
        
        return $this->execute($sql, $params);
    }
    
    public function delete(string $table, string $where, array $params = []): int {
        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        return $this->execute($sql, $params);
    }
    
    public function count(string $table, string $where = '1', array $params = []): int {
        $sql = sprintf("SELECT COUNT(*) FROM %s WHERE %s", $table, $where);
        $connection = $this->connection->getConnection();
        try {
            $this->statement = $connection->prepare($sql);
            $this->statement->execute($params);
            return (int)$this->statement->fetchColumn();
        } finally {
            $this->connection->releaseConnection($connection);
            $this->statement = null;
        }
    }
    
    public function transaction(callable $callback) {
        if ($this->inTransaction) {
            throw new PDOException("Already in transaction");
        }
        
        $connection = $this->connection->getConnection();
        try {
            $connection->beginTransaction();
            $this->inTransaction = true;
            
            $result = $callback($this);
            
            $connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        } finally {
            $this->inTransaction = false;
            $this->connection->releaseConnection($connection);
        }
    }
    
    public function getExecutionTime(): float {
        return round((microtime(true) - $this->startTime) * 1000, 2);
    }
    
    public function __destruct() {
        if ($this->statement) {
            $this->statement->closeCursor();
            $this->statement = null;
        }
    }
} 