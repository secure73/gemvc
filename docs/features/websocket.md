# WebSocket Features

## Overview

GEMVC provides a powerful WebSocket system built on OpenSwoole/Swoole, enabling real-time communication through the `SwooleWebSocketHandler` class. The system supports channel-based messaging, authentication, rate limiting, and Redis integration for scalability.

## Core Features

### 1. WebSocket Handler
- Real-time bidirectional communication
- Channel-based message handling
- Connection management with timeouts
- Rate limiting and spam prevention
- Heartbeat mechanism for connection health

### 2. Channel System
- Subscribe/unsubscribe to channels
- Channel-based message broadcasting
- Redis integration for multi-server scalability
- Channel membership tracking

### 3. Authentication & Security
- JWT-based authentication
- Role-based access control
- Rate limiting per connection
- Connection timeout management

## Configuration

### WebSocket Settings
```env
# WebSocket Configuration
WS_HOST=0.0.0.0
WS_PORT=9501
WS_SSL_ENABLED=false
WS_SSL_CERT=path/to/cert.pem
WS_SSL_KEY=path/to/key.pem
```

### Server Settings
```env
# Server Configuration
WS_WORKER_NUM=4
WS_DAEMONIZE=false
WS_LOG_LEVEL=1
WS_HEARTBEAT_INTERVAL=60
```

## Basic Usage

### Starting WebSocket Server
```php
<?php
require_once 'vendor/autoload.php';

use Gemvc\Http\SwooleWebSocketHandler;

// Check for Swoole/OpenSwoole
if (!extension_loaded('openswoole') && !extension_loaded('swoole')) {
    die('OpenSwoole or Swoole extension required');
}

// Create WebSocket handler
$handler = new SwooleWebSocketHandler([
    'connectionTimeout' => 300,
    'maxMessagesPerMinute' => 60,
    'heartbeatInterval' => 30
]);

// Create server (OpenSwoole example)
$server = new \OpenSwoole\WebSocket\Server('0.0.0.0', 9501);

// Register heartbeat
$handler->registerHeartbeat($server);

// Set up event handlers
$server->on('open', function($server, $request) use ($handler) {
    $handler->onOpen($server, $request);
});

$server->on('message', function($server, $frame) use ($handler) {
    $handler->onMessage($server, $frame);
});

$server->on('close', function($server, $fd) use ($handler) {
    $handler->onClose($server, $fd);
});

$server->start();
```

### Message Format
```json
{
    "action": "subscribe|message|unsubscribe",
    "data": {
        "channel": "chat.room.1",
        "message": "Hello everyone!",
        "recipients": [1, 2, 3]
    }
}
```

### Channel Operations
```php
// Subscribe to channel
$message = json_encode([
    'action' => 'subscribe',
    'data' => ['channel' => 'chat.room.1']
]);

// Send message to channel
$message = json_encode([
    'action' => 'message',
    'data' => [
        'channel' => 'chat.room.1',
        'message' => 'Hello everyone!'
    ]
]);

// Unsubscribe from channel
$message = json_encode([
    'action' => 'unsubscribe',
    'data' => ['channel' => 'chat.room.1']
]);
```

## Advanced Features

### Authentication
The WebSocket handler automatically attempts JWT authentication using the standard GEMVC authentication system:

```php
// Authentication is handled automatically in onOpen()
// Uses the same auth system as HTTP requests
if ($httpRequest->request->auth(['user', 'admin'])) {
    $connectionData['authenticated'] = true;
    $connectionData['user_id'] = $httpRequest->request->userId();
    $connectionData['role'] = $httpRequest->request->userRole();
}
```

### Rate Limiting
```php
// Configure rate limiting
$handler = new SwooleWebSocketHandler([
    'maxMessagesPerMinute' => 60  // 60 messages per minute per connection
]);
```

### Redis Integration
```php
$handler = new SwooleWebSocketHandler([
    'redis' => [
        'enabled' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => 'your_password',
        'database' => 0,
        'prefix' => 'websocket:'
    ]
]);
```

### Error Handling
```php
$server->on('error', function($server, $fd, $errno, $errstr) {
    // Log error
    $this->logError($errno, $errstr);
    
    // Notify client
    $server->push($fd, json_encode([
        'type' => 'error',
        'message' => 'Connection error occurred'
    ]));
});
```

## Best Practices

### 1. Server Configuration
- Set appropriate worker numbers
- Configure proper timeouts
- Enable SSL for production
- Set up proper logging

### 2. Event Handling
- Use meaningful event names
- Handle events asynchronously
- Implement proper error handling
- Log important events

### 3. Room Management
- Use meaningful room names
- Implement room persistence
- Handle room cleanup
- Monitor room usage

### 4. Security
- Implement proper authentication
- Validate all messages
- Use SSL in production
- Monitor connections

### 5. Performance
- Use connection pooling
- Implement message queuing
- Optimize message size
- Monitor server health

## Next Steps

- [WebSocket Components](../core/websocket-components.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 