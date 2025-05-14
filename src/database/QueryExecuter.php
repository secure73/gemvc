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

    private array $_cache = [];
    private array $_cacheConfig = [];
    private array $_cacheStats = ['hits' => 0, 'misses' => 0, 'sets' => 0];
    private array $_bindings = [];

    public function __construct()
    {
        $this->startExecutionTime = microtime(true);

        $this->_cacheConfig = [
            'enabled' => filter_var(getenv('DB_CACHE_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN),
            'ttl' => (int)(getenv('DB_CACHE_TTL_SEC') ?: 3600),
            'max_size' => (int)(getenv('DB_CACHE_MAX_QUERY_SIZE') ?: 1000),
            'include_patterns' => ['SELECT']
        ];
    }

    public function __destruct()
    {
        $this->secure();
    }

    private function ensureConnection(): bool
    {
        if (!$this->db) {
            try {
                $this->db = DBPoolManager::getInstance()->getConnection();  // Use DBPoolManager here
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

    private function generateCacheKey(string $query, array $params): string
    {
        $normalizedQuery = preg_replace('/\s+/', ' ', trim($query));
        return md5(serialize(['query' => $normalizedQuery, 'params' => $params]));
    }

    private function shouldCache(string $query): bool
    {
        if (!$this->_cacheConfig['enabled']) return false;

        foreach ($this->_cacheConfig['include_patterns'] as $pattern) {
            if (stripos($query, $pattern) === 0) return true;
        }

        return false;
    }

    private function getFromCache(string $key): ?array
    {
        if (!isset($this->_cache[$key])) {
            $this->_cacheStats['misses']++;
            return null;
        }

        if (time() > $this->_cache[$key]['expires']) {
            unset($this->_cache[$key]);
            $this->_cacheStats['misses']++;
            return null;
        }

        $this->_cacheStats['hits']++;
        return $this->_cache[$key]['data'];
    }

    private function setCache(string $key, array $data): void
    {
        $this->_cache[$key] = [
            'data' => $data,
            'created' => time(),
            'expires' => time() + $this->_cacheConfig['ttl']
        ];

        $this->_cacheStats['sets']++;

        if (count($this->_cache) > $this->_cacheConfig['max_size']) {
            $this->removeOldestCache();
        }
    }

    private function removeOldestCache(): void
    {
        uasort($this->_cache, fn($a, $b) => $a['created'] - $b['created']);
        $this->_cache = array_slice($this->_cache, ceil(count($this->_cache) * 0.9), null, true);
    }

    private function getBindings(): array
    {
        return $this->_bindings;
    }

    private function setCachedResult(array $cached): void
    {
        $this->stsment = null;
        $this->affectedRows = count($cached);
        $this->endExecutionTime = microtime(true);
        $this->error = null;
    }

    private function getResult(): array
    {
        return $this->stsment?->fetchAll(PDO::FETCH_ASSOC) ?? [];
    }

    public function execute(): bool
    {
        if ($this->shouldCache($this->_query)) {
            $cacheKey = $this->generateCacheKey($this->_query, $this->getBindings());
            if ($cached = $this->getFromCache($cacheKey)) {
                $this->setCachedResult($cached);
                return true;
            }
        }

        $result = $this->executeWithoutCache();

        if ($result && $this->shouldCache($this->_query)) {
            $this->setCache(
                $this->generateCacheKey($this->_query, $this->getBindings()),
                $this->getResult()
            );
        }

        return $result;
    }

    private function executeWithoutCache(): bool
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

    public function configureCache(array $config): void
    {
        $this->_cacheConfig = array_merge($this->_cacheConfig, $config);
    }

    public function secure(): void
    {
        $this->db = null;
        $this->stsment = null;
        $this->isConnected = false;
    }
}
