# Performance Guide

## Overview

GEMVC provides several features and best practices to optimize your application's performance. This guide covers performance optimization techniques, caching strategies, and database optimization.

## Core Performance Features

- Database connection pooling
- Query optimization
- Caching system
- WebSocket optimization
- File operation optimization
- Memory management
- Request/Response optimization

## Database Optimization

### 1. Connection Pooling

```php
use Gemvc\Database\PdoConnection;

// Configure connection pool
$config = [
    'min_connections' => 2,
    'max_connections' => 10,
    'max_age' => 3600
];

// Use connection pool
$pdo = new PdoConnection($config);
$pdo->connect();  // Gets connection from pool or creates new one
```

### 2. Query Optimization

```php
use Gemvc\Database\QueryBuilder;

// Optimize SELECT queries
$users = QueryBuilder::select('id', 'username', 'email')  // Select only needed columns
    ->from('users')
    ->whereEqual('status', 'active')
    ->limit(10)
    ->run($pdoQuery);

// Use indexes effectively
$generator = new TableGenerator();
$generator
    ->addIndex('status')  // Add index for frequently queried column
    ->addIndex('email', true)  // Add unique index
    ->createTableFromObject(new User());
```

### 3. Batch Operations

```php
use Gemvc\Database\Table;

class UserTable extends Table {
    public function batchInsert(array $users): bool {
        $values = [];
        $params = [];
        
        foreach ($users as $i => $user) {
            $values[] = "(:name{$i}, :email{$i})";
            $params["name{$i}"] = $user['name'];
            $params["email{$i}"] = $user['email'];
        }
        
        $sql = "INSERT INTO users (name, email) VALUES " . implode(',', $values);
        return $this->query($sql, $params)->execute();
    }
}
```

## Caching Strategies

### 1. Redis Caching

```php
use Gemvc\Core\RedisManager;

class CacheService {
    private RedisManager $redis;
    
    public function __construct() {
        $this->redis = new RedisManager();
    }
    
    public function getCachedData(string $key, callable $callback, int $ttl = 3600) {
        // Try to get from cache
        $data = $this->redis->get($key);
        
        if ($data === null) {
            // Cache miss, get fresh data
            $data = $callback();
            
            // Store in cache
            $this->redis->set($key, $data, $ttl);
        }
        
        return $data;
    }
}
```

### 2. Query Result Caching

```php
class UserService {
    public function getActiveUsers(): array {
        $cacheKey = 'active_users';
        
        return $this->cache->getCachedData($cacheKey, function() {
            return QueryBuilder::select('id', 'username')
                ->from('users')
                ->whereEqual('status', 'active')
                ->run($pdoQuery);
        }, 300);  // Cache for 5 minutes
    }
}
```

## WebSocket Optimization

### 1. Connection Management

```php
use Gemvc\WebSocket\SwooleWebSocketHandler;

class WebSocketServer {
    private SwooleWebSocketHandler $handler;
    
    public function __construct() {
        $this->handler = new SwooleWebSocketHandler([
            'connectionTimeout' => 300,
            'maxMessagesPerMinute' => 60,
            'heartbeatInterval' => 30
        ]);
    }
    
    public function onMessage($server, $frame) {
        // Check rate limit
        if (!$this->handler->checkRateLimit($frame->fd)) {
            return;
        }
        
        // Process message
        $this->handler->handleMessage($server, $frame);
    }
}
```

### 2. Message Batching

```php
class MessageBatcher {
    private array $messages = [];
    private int $batchSize = 100;
    
    public function addMessage($message): void {
        $this->messages[] = $message;
        
        if (count($this->messages) >= $this->batchSize) {
            $this->flush();
        }
    }
    
    public function flush(): void {
        if (!empty($this->messages)) {
            // Send batch of messages
            $this->sendBatch($this->messages);
            $this->messages = [];
        }
    }
}
```

## File Operations

### 1. Efficient File Processing

```php
use Gemvc\Helper\FileHelper;

class FileProcessor {
    public function processLargeFile(string $filePath): void {
        $file = new FileHelper($filePath);
        
        // Process in chunks
        $handle = fopen($filePath, 'r');
        while (!feof($handle)) {
            $chunk = fread($handle, 8192);  // 8KB chunks
            $this->processChunk($chunk);
        }
        fclose($handle);
    }
}
```

### 2. Image Optimization

```php
use Gemvc\Helper\ImageHelper;

class ImageOptimizer {
    public function optimizeImage(string $imagePath): void {
        $image = new ImageHelper($imagePath);
        
        // Convert to WebP for better compression
        $image->convertToWebP(80);
        
        // Set appropriate quality
        $image->setJpegQuality(75);
        $image->setPngQuality(9);
    }
}
```

## Memory Management

### 1. Resource Cleanup

```php
class ResourceManager {
    public function __destruct() {
        // Clean up resources
        $this->closeConnections();
        $this->clearCache();
        $this->flushLogs();
    }
    
    private function closeConnections(): void {
        // Close database connections
        $this->db->close();
        
        // Close Redis connection
        $this->redis->close();
    }
}
```

### 2. Memory Monitoring

```php
class MemoryMonitor {
    public function checkMemoryUsage(): void {
        $memoryLimit = ini_get('memory_limit');
        $currentUsage = memory_get_usage(true);
        
        if ($currentUsage > $this->getMemoryLimitInBytes($memoryLimit) * 0.8) {
            // Log warning
            $this->logWarning('High memory usage detected');
            
            // Force garbage collection
            gc_collect_cycles();
        }
    }
}
```

## Best Practices

### 1. Database
- Use connection pooling
- Optimize queries with proper indexes
- Implement query caching
- Use batch operations for bulk data
- Monitor query performance

### 2. Caching
- Implement appropriate cache strategies
- Use cache tags for invalidation
- Set reasonable TTL values
- Monitor cache hit rates
- Implement cache warming

### 3. WebSocket
- Implement connection pooling
- Use message batching
- Monitor connection health
- Implement rate limiting
- Use heartbeat mechanism

### 4. File Operations
- Process files in chunks
- Optimize image compression
- Use appropriate file formats
- Implement cleanup routines
- Monitor disk usage

## Performance Monitoring

### 1. Query Profiling

```php
class QueryProfiler {
    public function profileQuery(string $query, array $params): array {
        $startTime = microtime(true);
        
        // Execute query
        $result = $this->db->query($query, $params);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        return [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_usage(true)
        ];
    }
}
```

### 2. Performance Logging

```php
class PerformanceLogger {
    public function logMetrics(array $metrics): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics' => $metrics,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        $this->storeLog($logEntry);
    }
}
```

## Next Steps

- [Security Guide](security.md)
- [Deployment Guide](deployment.md)
- [Database Features](../features/database.md) 