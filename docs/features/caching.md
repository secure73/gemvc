# Redis Features

## Overview

GEMVC provides Redis integration through the RedisManager class, offering connection management, data storage, and pub/sub capabilities.

## Core Features

### 1. Redis Manager
- Connection management
- Key-value operations
- Data structures (Hash, List, Set, Sorted Set)
- Pub/Sub messaging
- Pipeline and transaction support

## Configuration

### Redis Settings
```env
# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DATABASE=0
REDIS_PREFIX=gemvc:
REDIS_PERSISTENT=false
REDIS_TIMEOUT=0.0
REDIS_READ_TIMEOUT=0.0
```

## Basic Usage

### Connection Management
```php
use Gemvc\Core\RedisManager;

$redis = RedisManager::getInstance();

// Connect to Redis
if ($redis->connect()) {
    // Connection successful
}

// Check connection status
if ($redis->isConnected()) {
    // Redis is connected
}

// Disconnect
$redis->disconnect();
```

### Key-Value Operations
```php
$redis = RedisManager::getInstance();

// Set value with optional TTL
$redis->set('key', 'value', 3600); // Expires in 1 hour
$redis->set('key', 'value'); // No expiration

// Get value
$value = $redis->get('key');

// Check if key exists
if ($redis->exists('key')) {
    // Key exists
}

// Delete key
$redis->delete('key');

// Get TTL
$ttl = $redis->ttl('key');

// Clear database
$redis->flush();
```

### Hash Operations
```php
$redis = RedisManager::getInstance();

// Set hash field
$redis->hSet('user:1', 'name', 'John');
$redis->hSet('user:1', 'email', 'john@example.com');

// Get hash field
$name = $redis->hGet('user:1', 'name');

// Get all hash fields
$userData = $redis->hGetAll('user:1');
```

### List Operations
```php
$redis = RedisManager::getInstance();

// Push to list
$redis->lPush('queue', 'item1');
$redis->rPush('queue', 'item2');

// Pop from list
$item = $redis->lPop('queue');
$item = $redis->rPop('queue');
```

### Set Operations
```php
$redis = RedisManager::getInstance();

// Add to set
$redis->sAdd('tags', 'php');
$redis->sAdd('tags', 'redis');

// Get set members
$tags = $redis->sMembers('tags');

// Check membership
if ($redis->sIsMember('tags', 'php')) {
    // 'php' is in the set
}
```

### Sorted Set Operations
```php
$redis = RedisManager::getInstance();

// Add to sorted set
$redis->zAdd('leaderboard', 100, 'player1');
$redis->zAdd('leaderboard', 200, 'player2');

// Get range
$topPlayers = $redis->zRange('leaderboard', 0, -1, true);
```

### Pub/Sub Operations
```php
$redis = RedisManager::getInstance();

// Publish message
$redis->publish('channel', 'message');

// Subscribe to channel
$redis->subscribe(['channel'], function($redis, $channel, $message) {
    // Handle message
});
```

### Pipeline and Transactions
```php
$redis = RedisManager::getInstance();

// Pipeline
$pipe = $redis->pipeline();
$pipe->set('key1', 'value1');
$pipe->set('key2', 'value2');
$pipe->execute();

// Transaction
$tx = $redis->transaction();
$tx->set('key1', 'value1');
$tx->set('key2', 'value2');
$tx->exec();
```

## Best Practices

### 1. Connection Management
- Use singleton pattern
- Handle connection errors
- Implement reconnection logic
- Monitor connection health

### 2. Data Operations
- Use appropriate data structures
- Set TTL for temporary data
- Use pipelines for bulk operations
- Handle operation errors

### 3. Security
- Use Redis password
- Set appropriate permissions
- Use key prefixes
- Monitor Redis access

### 4. Performance
- Use pipelines for bulk operations
- Implement connection pooling
- Monitor memory usage
- Clean up expired keys

### 5. Error Handling
- Handle connection failures
- Implement retry logic
- Log Redis errors
- Monitor Redis health

## Next Steps

- [Performance Guide](../guides/performance.md)
- [Security Guide](../guides/security.md)
- [WebSocket Guide](../guides/websocket.md) 