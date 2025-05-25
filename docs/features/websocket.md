# WebSocket Features

## Overview

GEMVC provides a powerful WebSocket system built on OpenSwoole, enabling real-time communication, event handling, and room management.

## Core Features

### 1. WebSocket Server
- Real-time bidirectional communication
- Event-based message handling
- Room management
- Connection pooling

### 2. Event System
- Custom event handling
- Event broadcasting
- Event filtering
- Event logging

### 3. Room Management
- Dynamic room creation
- Room membership
- Room broadcasting
- Room persistence

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
use Gemvc\WebSocket\WebSocketServer;

$server = new WebSocketServer();

$server->on('start', function($server) {
    echo "WebSocket server started\n";
});

$server->on('connect', function($server, $fd) {
    echo "Client {$fd} connected\n";
});

$server->on('message', function($server, $fd, $data) {
    $server->push($fd, "Received: {$data}");
});

$server->on('close', function($server, $fd) {
    echo "Client {$fd} closed\n";
});

$server->start();
```

### Event Handling
```php
use Gemvc\WebSocket\EventManager;

$events = new EventManager();

// Register event handler
$events->on('user.login', function($data) {
    // Handle user login
    $this->broadcast('user.status', [
        'user_id' => $data['user_id'],
        'status' => 'online'
    ]);
});

// Trigger event
$events->trigger('user.login', [
    'user_id' => 1,
    'username' => 'john'
]);
```

### Room Management
```php
use Gemvc\WebSocket\RoomManager;

$rooms = new RoomManager();

// Create room
$rooms->create('chat.1');

// Join room
$rooms->join('chat.1', $fd);

// Leave room
$rooms->leave('chat.1', $fd);

// Broadcast to room
$rooms->broadcast('chat.1', [
    'type' => 'message',
    'content' => 'Hello everyone!'
]);
```

## Advanced Features

### Authentication
```php
$server->on('handshake', function($request, $response) {
    // Verify token
    $token = $request->header['authorization'];
    if (!$this->verifyToken($token)) {
        return false;
    }
    
    // Set user data
    $user = $this->getUserFromToken($token);
    $request->user = $user;
    
    return true;
});
```

### Message Filtering
```php
$server->on('message', function($server, $fd, $data) {
    // Filter message
    $filtered = $this->filterMessage($data);
    
    // Broadcast filtered message
    $this->broadcast('chat.message', $filtered);
});
```

### Connection Pooling
```php
$server->set([
    'worker_num' => 4,
    'max_connection' => 1000,
    'heartbeat_idle_time' => 60,
    'heartbeat_check_interval' => 10
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

- [Real-time Features](../guides/real-time.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 