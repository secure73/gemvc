<?php
namespace Gemvc\Database;

use PDO;
use PDOStatement;
use Gemvc\Database\DBPoolManager;

class QueryExecuter
{
    private ?string $error = null;
    private int $affectedRows = 0;
    private string|false $lastInsertedId = false;
    private ?PDOStatement $stsment = null;
    private float $startExecutionTime;
    private ?float $endExecutionTime = null;
    private string $_query = '';
    private ?PDO $db = null;
    private bool $isConnected = false;
    private array $_bindings = [];

    public function __construct()
    {
        $this->startExecutionTime = microtime(true);
    }

    public function __destruct()
    {
        $this->secure();
    }

    private function ensureConnection(): bool
    {
        if (!$this->db) {
            try {
                $this->db = DBPoolManager::getInstance()->getConnection();
                $this->isConnected = $this->db instanceof PDO;
            } catch (\Throwable $e) {
                $this->error = 'Failed to get DB connection: ' . $e->getMessage();
                $this->isConnected = false;
            }
        }

        return $this->isConnected;
    }

    public function getQuery(): ?string
    {
        return $this->_query ?: null;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function query(string $query): void
    {
        $this->_query = $query;

        if (!$this->ensureConnection()) {
            $this->error = 'Database connection is null.';
            return;
        }

        try {
            $this->stsment = $this->db->prepare($query);
        } catch (\PDOException $e) {
            $this->error = 'Error preparing statement: ' . $e->getMessage();
        }
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function bind(string $param, mixed $value): void
    {
        if (!$this->stsment) {
            $this->error = 'Cannot bind parameters: No statement prepared';
            return;
        }

        $type = match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };

        $this->stsment->bindValue($param, $value, $type);
        $this->_bindings[$param] = $value;
    }

    private function getBindings(): array
    {
        return $this->_bindings;
    }

    public function execute(): bool
    {
        if (!$this->ensureConnection()) {
            $this->error = 'Database connection not established';
            $this->endExecutionTime = microtime(true);
            return false;
        }

        if (!$this->stsment) {
            $this->error = 'No statement prepared';
            $this->endExecutionTime = microtime(true);
            return false;
        }

        try {
            $this->stsment->execute();
            $this->affectedRows = $this->stsment->rowCount();
            $this->lastInsertedId = $this->db->lastInsertId();
            $this->error = null;
            $this->endExecutionTime = microtime(true);
            return true;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            $this->endExecutionTime = microtime(true);
            return false;
        }
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    public function getLastInsertedId(): false|string
    {
        return $this->lastInsertedId;
    }

    public function getExecutionTime(): float
    {
        return round(($this->endExecutionTime - $this->startExecutionTime) * 1000, 2);
    }

    public function fetchAllObjects(): array|false
    {
        if (!$this->stsment) {
            $this->error = 'No statement prepared for fetching objects.';
            return false;
        }
        try {
            return $this->stsment->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->error = 'Error fetching objects: ' . $e->getMessage();
            return false;
        }
    }

    public function fetchAll(): array|false
    {
        if (!$this->stsment) {
            $this->error = 'No statement prepared for fetching results.';
            return false;
        }
        try {
            return $this->stsment->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->error = 'Error fetching results: ' . $e->getMessage();
            return false;
        }
    }

    public function fetchColumn(): mixed
    {
        if (!$this->stsment) {
            $this->error = 'No statement prepared for fetching a column.';
            return false;
        }
        try {
            return $this->stsment->fetchColumn();
        } catch (\PDOException $e) {
            $this->error = 'Error fetching column: ' . $e->getMessage();
            return false;
        }
    }

    public function secure(): void
    {
        if ($this->db) {
            DBPoolManager::getInstance()->release($this->db);
        }
        $this->db = null;
        $this->stsment = null;
        $this->isConnected = false;
    }

    public function releaseConnection(): void
    {
        $this->secure();
    }
}
