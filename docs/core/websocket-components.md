# WebSocket Components

## Overview

GEMVC provides WebSocket functionality through the `SwooleWebSocketHandler` class, which is built on top of OpenSwoole/Swoole for high-performance real-time communication.

## Core Components

### SwooleWebSocketHandler

The main WebSocket handler class located in `src/http/SwooleWebSocketHandler.php`.

#### Features
- **Connection Management**: Tracks active connections with timeout handling
- **Rate Limiting**: Prevents spam with configurable message limits
- **Heartbeat System**: Maintains connection health with ping/pong
- **Channel System**: Subscribe/unsubscribe to message channels
- **Redis Integration**: Optional Redis backend for scalability
- **Authentication**: JWT-based authentication support

#### Requirements
- OpenSwoole or Swoole extension
- Redis extension (optional, for scalability)

## Configuration

### Basic Configuration
```php
use Gemvc\Http\SwooleWebSocketHandler;

$handler = new SwooleWebSocketHandler([
    'connectionTimeout' => 300,        // seconds
    'maxMessagesPerMinute' => 60,      // rate limit
    'heartbeatInterval' => 30,         // seconds
    'redis' => [
        'enabled' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => 0,
        'prefix' => 'websocket:'
    ]
]);
```

### Environment Variables
```env
# Redis Configuration (optional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
REDIS_PREFIX=websocket:
```

## Usage Examples

### Basic WebSocket Server Setup
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

## Response Messages

### Welcome Message
```json
{
    "action": "welcome",
    "heartbeat_interval": 30,
    "connection_id": 1,
    "authenticated": true
}
```

### Success Responses
```json
{
    "success": true,
    "action": "subscribe",
    "channel": "chat.room.1"
}
```

### Error Responses
```json
{
    "success": false,
    "error": "Rate limit exceeded. Please slow down.",
    "retry_after": 60
}
```

## Advanced Features

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

### Authentication
The handler automatically attempts JWT authentication using the standard GEMVC authentication system:

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

## Best Practices

### 1. Server Configuration
- Set appropriate connection timeouts
- Configure rate limiting based on your use case
- Enable Redis for production scalability
- Monitor connection health

### 2. Message Handling
- Always validate message format
- Implement proper error handling
- Use meaningful channel names
- Handle authentication properly

### 3. Performance
- Use Redis for multi-server deployments
- Monitor memory usage
- Implement proper cleanup
- Use appropriate heartbeat intervals

### 4. Security
- Validate all incoming messages
- Implement proper authentication
- Use SSL in production
- Monitor for abuse

## Error Handling

### Common Errors
```json
{
    "success": false,
    "error": "Invalid message format"
}
```

```json
{
    "success": false,
    "error": "Not subscribed to channel"
}
```

```json
{
    "success": false,
    "error": "Rate limit exceeded. Please slow down.",
    "retry_after": 60
}
```

## Integration with GEMVC

### Using with ApiService
```php
use Gemvc\Core\ApiService;
use Gemvc\Http\SwooleWebSocketHandler;

class ChatService extends ApiService {
    private SwooleWebSocketHandler $wsHandler;
    
    public function __construct(Request $request) {
        parent::__construct($request);
        $this->wsHandler = new SwooleWebSocketHandler();
    }
    
    public function broadcastMessage(): JsonResponse {
        // Validate request
        if (!$this->request->auth(['user'])) {
            return $this->request->returnResponse();
        }
        
        // Your broadcast logic here
        return Response::success('Message broadcasted');
    }
}
```

## Next Steps

- [WebSocket Features](../features/websocket.md)
- [Performance Guide](../guides/performance.md)
- [Security Guide](../guides/security.md) 