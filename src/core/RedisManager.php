<?php

namespace Gemvc\Core;

use Redis;
use Gemvc\Core\RedisConnectionException;
use Gemvc\Http\JsonResponse;

class RedisManager
{
    private static ?RedisManager $instance = null;
    private ?Redis $redis = null;
    private bool $isConnected = false;
    private string $_host;
    private int $_port;
    private float $_timeout;
    private string $_password;
    private int $_database;
    private string $_prefix;
    private bool $_persistent;
    private float $_read_timeout;
    private ?string $_error = null;

    private function __construct()
    {
        $this->_error = null;
        $this->_host = $this->getHost();
        $this->_port = $this->getPort();
        $this->_timeout = $this->getTimeout();
        $this->_password = $this->getPassword();
        $this->_database = $this->getDatabase();
        $this->_prefix = $this->getPrefix();
        $this->_persistent = $this->getPersistent();
        $this->_read_timeout = $this->getReadTimeout();
    }

    private function getHost(): string
    {
        return $this->getEnvString('REDIS_HOST', '127.0.0.1');
    }

    private function getPort(): int
    {
        return $this->getEnvInt('REDIS_PORT', 6379);
    }

    private function getTimeout(): float
    {
        return $this->getEnvFloat('REDIS_TIMEOUT', 0.0);
    }

    private function getPassword(): string  
    {
        return $this->getEnvString('REDIS_PASSWORD');
    }

    private function getDatabase(): int
    {       
        return $this->getEnvInt('REDIS_DATABASE', 0);
    }

    private function getPrefix(): string
    {
        return $this->getEnvString('REDIS_PREFIX', 'gemvc:');
    }   

    private function getPersistent(): bool
    {
        return $this->getEnvBool('REDIS_PERSISTENT', false);
    }   

    private function getReadTimeout(): float
    {
        return $this->getEnvFloat('REDIS_READ_TIMEOUT', 0.0);
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

    /**
     * Get the singleton instance of RedisManager
     * 
     * @return self
     * @example
     * $redis = RedisManager::getInstance();
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Connect to Redis server
     * 
     * @return void
     * @throws RedisConnectionException if connection fails
     * @example
     * $redis = RedisManager::getInstance();
     * $redis->connect();
     */
    public function connect(): bool
    {
        if ($this->isConnected) {
            return true;
        }

        try {
            $this->redis = new Redis();
            
            if ($this->_persistent) {
                $this->redis->pconnect(
                    $this->_host,
                    $this->_port,
                    $this->_timeout
                );
            } else {
                $this->redis->connect(
                    $this->_host,
                    $this->_port,
                    $this->_timeout
                );
            }

            if ($this->_password) {
                $this->redis->auth($this->_password);
            }

            if ($this->_database > 0) {
                $this->redis->select($this->_database);
            }

            $this->redis->setOption(Redis::OPT_PREFIX, $this->_prefix);
            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, $this->_read_timeout);

            $this->isConnected = true;
            return true;
        } catch (\Exception $e) {
            $this->_error = $e->getMessage();
            return false;   
        }
    }

    public function disconnect(): void
    {

        if ($this->redis && $this->isConnected) {
            $this->redis->close();
            $this->isConnected = false;
        }
    }

    /**
     * Get the Redis instance. Will auto-connect if not connected.
     * 
     * @return Redis
     * @example
     * $redis = RedisManager::getInstance();
     * $redisInstance = $redis->getRedis();
     */
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

    public function getError(): ?string
    {
        return $this->_error;
    }

    /**
     * Set a value in Redis with optional TTL
     * 
     * @param string $key The key to set
     * @param mixed $value The value to store
     * @param int|null $ttl Time to live in seconds (optional)
     * @return bool
     * @example
     * $redis->set('user:1', 'John', 3600); // Expires in 1 hour
     * $redis->set('config', 'value'); // No expiration
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if ($ttl !== null) {
            return $this->getRedis()->setex($key, $ttl, $value);
        }
        return $this->getRedis()->set($key, $value);
    }

    public function setJsonResponse(string $key, JsonResponse $response, ?int $ttl = null): bool
    {
        return $this->set($key, serialize($response), $ttl);
    }

    public function getJsonResponse(string $key): null|JsonResponse
    {
        $data = $this->get($key);
        if ($data === null) {
            return null;
        }
        $response = unserialize($data);
        if ($response instanceof JsonResponse) {
            return $response;
        }
        return null;
    }

    /**
     * Get a value from Redis
     * 
     * @param string $key The key to retrieve
     * @return mixed The stored value or null if not found
     * @example
     * $value = $redis->get('user:1');
     */
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

    /**
     * Store multiple fields in a Redis hash
     * 
     * @param string $key The hash key
     * @param string $field The field name
     * @param mixed $value The value to store
     * @return int
     * @example
     * $redis->hSet('user:1', 'name', 'John');
     * $redis->hSet('user:1', 'email', 'john@example.com');
     */
    public function hSet(string $key, string $field, $value): int
    {
        return $this->getRedis()->hSet($key, $field, $value);
    }

    public function hGet(string $key, string $field)
    {
        return $this->getRedis()->hGet($key, $field);
    }

    /**
     * Get all fields and values from a Redis hash
     * 
     * @param string $key The hash key
     * @return array
     * @example
     * $userData = $redis->hGetAll('user:1');
     * // Returns: ['name' => 'John', 'email' => 'john@example.com']
     */
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

    /**
     * Add a member to a Redis set
     * 
     * @param string $key The set key
     * @param mixed $value The value to add
     * @return int
     * @example
     * $redis->sAdd('tags', 'php');
     * $redis->sAdd('tags', 'redis');
     */
    public function sAdd(string $key, $value): int
    {
        return $this->getRedis()->sAdd($key, $value);
    }

    /**
     * Get all members of a Redis set
     * 
     * @param string $key The set key
     * @return array
     * @example
     * $tags = $redis->sMembers('tags');
     * // Returns: ['php', 'redis']
     */
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

    /**
     * Publish a message to a Redis channel
     * 
     * @param string $channel The channel name
     * @param string $message The message to publish
     * @return int
     * @example
     * $redis->publish('news', 'Hello World');
     */
    public function publish(string $channel, string $message): int
    {
        return $this->getRedis()->publish($channel, $message);
    }

    public function subscribe(array $channels, callable $callback): void
    {
        $this->getRedis()->subscribe($channels, $callback);
    }

    /**
     * Start a Redis pipeline for multiple operations
     * 
     * @return \Redis
     * @example
     * $pipe = $redis->pipeline();
     * $pipe->set('key1', 'value1');
     * $pipe->set('key2', 'value2');
     * $pipe->execute();
     */
    public function pipeline(): \Redis
    {
        return $this->getRedis()->multi(Redis::PIPELINE);
    }

    public function transaction(): \Redis
    {
        return $this->getRedis()->multi(Redis::MULTI);
    }
} 