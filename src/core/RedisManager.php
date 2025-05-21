<?php

namespace GEMVC\Core;

use Redis;
use GEMVC\Core\RedisConnectionException;

class RedisManager
{
    private static ?RedisManager $instance = null;
    private ?Redis $redis = null;
    private array $config = [];
    private bool $isConnected = false;

    private function __construct()
    {
        $this->config = [
            'host' => $this->getEnvString('REDIS_HOST', '127.0.0.1'),
            'port' => $this->getEnvInt('REDIS_PORT', 6379),
            'timeout' => $this->getEnvFloat('REDIS_TIMEOUT', 0.0),
            'password' => $this->getEnvString('REDIS_PASSWORD'),
            'database' => $this->getEnvInt('REDIS_DATABASE', 0),
            'prefix' => $this->getEnvString('REDIS_PREFIX', 'gemvc:'),
            'persistent' => $this->getEnvBool('REDIS_PERSISTENT', false),
            'read_timeout' => $this->getEnvFloat('REDIS_READ_TIMEOUT', 0.0),
        ];
    }

    private function getEnvString(string $key, ?string $default = null): ?string
    {
        return $_ENV[$key] ?? $default;
    }

    private function getEnvInt(string $key, int $default = 0): int
    {
        return (int)($_ENV[$key] ?? $default);
    }

    private function getEnvFloat(string $key, float $default = 0.0): float
    {
        return (float)($_ENV[$key] ?? $default);
    }

    private function getEnvBool(string $key, bool $default = false): bool
    {
        return (bool)($_ENV[$key] ?? $default);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function connect(): void
    {
        if ($this->isConnected) {
            return;
        }

        try {
            $this->redis = new Redis();
            
            if ($this->config['persistent']) {
                $this->redis->pconnect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout']
                );
            } else {
                $this->redis->connect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout']
                );
            }

            if ($this->config['password']) {
                $this->redis->auth($this->config['password']);
            }

            if ($this->config['database'] > 0) {
                $this->redis->select($this->config['database']);
            }

            $this->redis->setOption(Redis::OPT_PREFIX, $this->config['prefix']);
            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, $this->config['read_timeout']);

            $this->isConnected = true;
        } catch (\Exception $e) {
            throw new RedisConnectionException(
                "Failed to connect to Redis: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function disconnect(): void
    {
        if ($this->redis && $this->isConnected) {
            $this->redis->close();
            $this->isConnected = false;
        }
    }

    public function getRedis(): Redis
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->redis;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    // Basic Redis Operations
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if ($ttl !== null) {
            return $this->getRedis()->setex($key, $ttl, $value);
        }
        return $this->getRedis()->set($key, $value);
    }

    public function get(string $key)
    {
        return $this->getRedis()->get($key);
    }

    public function delete(string $key): int
    {
        return $this->getRedis()->del($key);
    }

    public function exists(string $key): bool
    {
        return (bool)$this->getRedis()->exists($key);
    }

    public function ttl(string $key): int
    {
        return $this->getRedis()->ttl($key);
    }

    public function flush(): bool
    {
        return $this->getRedis()->flushDB();
    }

    // Hash Operations
    public function hSet(string $key, string $field, $value): int
    {
        return $this->getRedis()->hSet($key, $field, $value);
    }

    public function hGet(string $key, string $field)
    {
        return $this->getRedis()->hGet($key, $field);
    }

    public function hGetAll(string $key): array
    {
        return $this->getRedis()->hGetAll($key);
    }

    // List Operations
    public function lPush(string $key, $value): int
    {
        return $this->getRedis()->lPush($key, $value);
    }

    public function rPush(string $key, $value): int
    {
        return $this->getRedis()->rPush($key, $value);
    }

    public function lPop(string $key)
    {
        return $this->getRedis()->lPop($key);
    }

    public function rPop(string $key)
    {
        return $this->getRedis()->rPop($key);
    }

    // Set Operations
    public function sAdd(string $key, $value): int
    {
        return $this->getRedis()->sAdd($key, $value);
    }

    public function sMembers(string $key): array
    {
        return $this->getRedis()->sMembers($key);
    }

    public function sIsMember(string $key, $value): bool
    {
        return $this->getRedis()->sIsMember($key, $value);
    }

    // Sorted Set Operations
    public function zAdd(string $key, float $score, $value): int
    {
        return $this->getRedis()->zAdd($key, $score, $value);
    }

    public function zRange(string $key, int $start, int $end, bool $withScores = false): array
    {
        return $this->getRedis()->zRange($key, $start, $end, $withScores);
    }

    // Pub/Sub Operations
    public function publish(string $channel, string $message): int
    {
        return $this->getRedis()->publish($channel, $message);
    }

    public function subscribe(array $channels, callable $callback): void
    {
        $this->getRedis()->subscribe($channels, $callback);
    }

    // Pipeline Operations
    public function pipeline(): \Redis
    {
        return $this->getRedis()->multi(Redis::PIPELINE);
    }

    public function transaction(): \Redis
    {
        return $this->getRedis()->multi(Redis::MULTI);
    }
} 