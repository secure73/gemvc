# WebSocket Guide

## Overview

This guide provides comprehensive information about implementing and using WebSocket functionality in GEMVC applications. WebSockets enable real-time, bidirectional communication between clients and servers.

## WebSocket Architecture

### Core Components

#### SwooleWebSocketHandler
The main WebSocket handler class provides:
- **Connection Management**: Automatic connection tracking and cleanup
- **Channel System**: Subscribe/unsubscribe to message channels
- **Rate Limiting**: Prevent spam with configurable limits
- **Authentication**: JWT-based user authentication
- **Redis Integration**: Scalable multi-server support

### Server Setup

#### Basic WebSocket Server
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

#### Production Configuration
```php
<?php
// config/websocket.php

return [
    'host' => '0.0.0.0',
    'port' => 9501,
    'settings' => [
        'worker_num' => 4,
        'max_request' => 10000,
        'enable_static_handler' => true,
        'document_root' => __DIR__ . '/../public',
        'log_level' => SWOOLE_LOG_ERROR,
        'pid_file' => __DIR__ . '/../storage/swoole.pid',
        'log_file' => __DIR__ . '/../storage/swoole.log',
    ],
    'handler' => [
        'connectionTimeout' => 300,
        'maxMessagesPerMinute' => 60,
        'heartbeatInterval' => 30,
        'redis' => [
            'enabled' => true,
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'database' => 0,
            'prefix' => 'websocket:'
        ]
    ]
];
```

## Client Implementation

### JavaScript Client
```javascript
class WebSocketClient {
    constructor(url) {
        this.url = url;
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.subscriptions = new Set();
        
        this.connect();
    }
    
    connect() {
        try {
            this.ws = new WebSocket(this.url);
            
            this.ws.onopen = () => {
                console.log('Connected to WebSocket server');
                this.reconnectAttempts = 0;
                
                // Resubscribe to channels
                this.subscriptions.forEach(channel => {
                    this.subscribe(channel);
                });
            };
            
            this.ws.onmessage = (event) => {
                this.handleMessage(JSON.parse(event.data));
            };
            
            this.ws.onclose = () => {
                console.log('Disconnected from WebSocket server');
                this.handleReconnect();
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
            
        } catch (error) {
            console.error('Failed to connect:', error);
            this.handleReconnect();
        }
    }
    
    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Reconnecting... Attempt ${this.reconnectAttempts}`);
            
            setTimeout(() => {
                this.connect();
            }, this.reconnectDelay * this.reconnectAttempts);
        } else {
            console.error('Max reconnection attempts reached');
        }
    }
    
    subscribe(channel) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                action: 'subscribe',
                data: { channel: channel }
            }));
            this.subscriptions.add(channel);
        }
    }
    
    unsubscribe(channel) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                action: 'unsubscribe',
                data: { channel: channel }
            }));
            this.subscriptions.delete(channel);
        }
    }
    
    sendMessage(channel, message) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                action: 'message',
                data: {
                    channel: channel,
                    message: message
                }
            }));
        }
    }
    
    handleMessage(data) {
        switch (data.action) {
            case 'welcome':
                console.log('Welcome message received');
                break;
                
            case 'message':
                this.onMessage(data);
                break;
                
            case 'ping':
                this.sendPong();
                break;
                
            case 'error':
                console.error('WebSocket error:', data.error);
                break;
                
            default:
                console.log('Unknown message type:', data.action);
        }
    }
    
    sendPong() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                action: 'pong'
            }));
        }
    }
    
    onMessage(data) {
        // Override this method to handle incoming messages
        console.log('Message received:', data);
    }
    
    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }
}

// Usage
const wsClient = new WebSocketClient('ws://localhost:9501');

// Subscribe to channels
wsClient.subscribe('chat.room.1');
wsClient.subscribe('notifications');

// Send message
wsClient.sendMessage('chat.room.1', 'Hello everyone!');
```

### React Component Example
```jsx
import React, { useEffect, useState, useRef } from 'react';

const WebSocketChat = () => {
    const [messages, setMessages] = useState([]);
    const [inputMessage, setInputMessage] = useState('');
    const [isConnected, setIsConnected] = useState(false);
    const wsRef = useRef(null);
    
    useEffect(() => {
        // Initialize WebSocket connection
        wsRef.current = new WebSocket('ws://localhost:9501');
        
        wsRef.current.onopen = () => {
            setIsConnected(true);
            console.log('Connected to WebSocket');
            
            // Subscribe to chat channel
            wsRef.current.send(JSON.stringify({
                action: 'subscribe',
                data: { channel: 'chat.room.1' }
            }));
        };
        
        wsRef.current.onmessage = (event) => {
            const data = JSON.parse(event.data);
            
            if (data.action === 'message') {
                setMessages(prev => [...prev, {
                    id: Date.now(),
                    sender: data.sender,
                    message: data.message,
                    timestamp: data.time
                }]);
            }
        };
        
        wsRef.current.onclose = () => {
            setIsConnected(false);
            console.log('Disconnected from WebSocket');
        };
        
        return () => {
            if (wsRef.current) {
                wsRef.current.close();
            }
        };
    }, []);
    
    const sendMessage = () => {
        if (inputMessage.trim() && wsRef.current) {
            wsRef.current.send(JSON.stringify({
                action: 'message',
                data: {
                    channel: 'chat.room.1',
                    message: inputMessage
                }
            }));
            setInputMessage('');
        }
    };
    
    return (
        <div className="chat-container">
            <div className="connection-status">
                Status: {isConnected ? 'Connected' : 'Disconnected'}
            </div>
            
            <div className="messages">
                {messages.map(msg => (
                    <div key={msg.id} className="message">
                        <strong>{msg.sender}:</strong> {msg.message}
                    </div>
                ))}
            </div>
            
            <div className="input-container">
                <input
                    type="text"
                    value={inputMessage}
                    onChange={(e) => setInputMessage(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
                    placeholder="Type your message..."
                    disabled={!isConnected}
                />
                <button onClick={sendMessage} disabled={!isConnected}>
                    Send
                </button>
            </div>
        </div>
    );
};

export default WebSocketChat;
```

## Message Protocol

### Message Format
All WebSocket messages follow a consistent JSON format:

```json
{
    "action": "subscribe|message|unsubscribe|ping|pong",
    "data": {
        "channel": "channel_name",
        "message": "message_content",
        "recipients": [1, 2, 3]
    }
}
```

### Supported Actions

#### Subscribe
```json
{
    "action": "subscribe",
    "data": {
        "channel": "chat.room.1"
    }
}
```

#### Message
```json
{
    "action": "message",
    "data": {
        "channel": "chat.room.1",
        "message": "Hello everyone!",
        "recipients": [1, 2, 3]
    }
}
```

#### Unsubscribe
```json
{
    "action": "unsubscribe",
    "data": {
        "channel": "chat.room.1"
    }
}
```

### Response Messages

#### Welcome Message
```json
{
    "action": "welcome",
    "heartbeat_interval": 30,
    "connection_id": 1,
    "authenticated": true
}
```

#### Success Response
```json
{
    "success": true,
    "action": "subscribe",
    "channel": "chat.room.1"
}
```

#### Error Response
```json
{
    "success": false,
    "error": "Rate limit exceeded. Please slow down.",
    "retry_after": 60
}
```

## Authentication

### JWT Authentication
The WebSocket handler automatically attempts JWT authentication:

```php
// Authentication is handled automatically in onOpen()
if ($httpRequest->request->auth(['user', 'admin'])) {
    $connectionData['authenticated'] = true;
    $connectionData['user_id'] = $httpRequest->request->userId();
    $connectionData['role'] = $httpRequest->request->userRole();
}
```

### Client Authentication
```javascript
// Include JWT token in WebSocket connection
const token = localStorage.getItem('jwt_token');
const ws = new WebSocket(`ws://localhost:9501?token=${token}`);

// Or use Authorization header (if supported by your setup)
const ws = new WebSocket('ws://localhost:9501');
ws.onopen = () => {
    ws.send(JSON.stringify({
        action: 'authenticate',
        data: { token: token }
    }));
};
```

## Channel Management

### Channel Types

#### Public Channels
```php
// Anyone can subscribe
$handler->subscribe('public.chat');
$handler->subscribe('public.notifications');
```

#### Private Channels
```php
// Only authenticated users can subscribe
if ($connectionData['authenticated']) {
    $handler->subscribe('private.user.' . $connectionData['user_id']);
}
```

#### Presence Channels
```php
// Channels that track user presence
$handler->subscribe('presence.chat.room.1');
```

### Channel Naming Conventions
- Use dot notation for hierarchy: `chat.room.1`
- Prefix private channels: `private.user.123`
- Use descriptive names: `notifications.system`
- Avoid special characters except dots and underscores

## Rate Limiting

### Configuration
```php
$handler = new SwooleWebSocketHandler([
    'maxMessagesPerMinute' => 60  // 60 messages per minute per connection
]);
```

### Client-side Rate Limiting
```javascript
class RateLimitedWebSocketClient extends WebSocketClient {
    constructor(url, maxMessagesPerMinute = 60) {
        super(url);
        this.maxMessagesPerMinute = maxMessagesPerMinute;
        this.messageCount = 0;
        this.messageWindow = Date.now();
    }
    
    sendMessage(channel, message) {
        const now = Date.now();
        
        // Reset counter if window has passed
        if (now - this.messageWindow > 60000) {
            this.messageCount = 0;
            this.messageWindow = now;
        }
        
        // Check rate limit
        if (this.messageCount >= this.maxMessagesPerMinute) {
            console.warn('Rate limit exceeded');
            return false;
        }
        
        // Send message
        super.sendMessage(channel, message);
        this.messageCount++;
        
        return true;
    }
}
```

## Error Handling

### Server-side Error Handling
```php
try {
    $handler->onMessage($server, $frame);
} catch (Exception $e) {
    error_log('WebSocket error: ' . $e->getMessage());
    
    // Send error response to client
    $server->push($frame->fd, json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]));
}
```

### Client-side Error Handling
```javascript
class RobustWebSocketClient extends WebSocketClient {
    constructor(url) {
        super(url);
        this.errorHandlers = new Map();
    }
    
    onError(error) {
        console.error('WebSocket error:', error);
        
        // Call registered error handlers
        this.errorHandlers.forEach(handler => {
            try {
                handler(error);
            } catch (e) {
                console.error('Error in error handler:', e);
            }
        });
    }
    
    addErrorHandler(type, handler) {
        this.errorHandlers.set(type, handler);
    }
    
    removeErrorHandler(type) {
        this.errorHandlers.delete(type);
    }
}

// Usage
const wsClient = new RobustWebSocketClient('ws://localhost:9501');

wsClient.addErrorHandler('connection', (error) => {
    // Handle connection errors
    showNotification('Connection lost. Reconnecting...');
});

wsClient.addErrorHandler('rate_limit', (error) => {
    // Handle rate limit errors
    showNotification('Too many messages. Please slow down.');
});
```

## Performance Optimization

### Connection Pooling
```php
// Configure connection limits
$handler = new SwooleWebSocketHandler([
    'connectionTimeout' => 300,
    'maxConnections' => 1000
]);
```

### Message Batching
```php
// Batch multiple updates
$batchUpdates = [
    ['type' => 'user_joined', 'user_id' => 1],
    ['type' => 'message_sent', 'message_id' => 123],
    ['type' => 'status_changed', 'user_id' => 2]
];

$handler->broadcastBatch($batchUpdates);
```

### Memory Management
```php
// Clean up expired connections
$handler->cleanupExpiredConnections($server);

// Monitor memory usage
$memoryUsage = memory_get_usage(true);
if ($memoryUsage > 100 * 1024 * 1024) { // 100MB
    // Trigger garbage collection
    gc_collect_cycles();
}
```

## Monitoring and Debugging

### Server Monitoring
```php
// Log connection events
$server->on('open', function($server, $request) {
    error_log("Client {$request->fd} connected from {$request->server['remote_addr']}");
});

$server->on('close', function($server, $fd) {
    error_log("Client {$fd} disconnected");
});
```

### Client Monitoring
```javascript
class MonitoredWebSocketClient extends WebSocketClient {
    constructor(url) {
        super(url);
        this.metrics = {
            messagesSent: 0,
            messagesReceived: 0,
            reconnections: 0,
            errors: 0
        };
    }
    
    sendMessage(channel, message) {
        super.sendMessage(channel, message);
        this.metrics.messagesSent++;
    }
    
    handleMessage(data) {
        super.handleMessage(data);
        this.metrics.messagesReceived++;
    }
    
    handleReconnect() {
        super.handleReconnect();
        this.metrics.reconnections++;
    }
    
    onError(error) {
        super.onError(error);
        this.metrics.errors++;
    }
    
    getMetrics() {
        return { ...this.metrics };
    }
}
```

## Best Practices

### 1. Connection Management
- Always handle connection errors
- Implement automatic reconnection
- Clean up resources on disconnect

### 2. Message Handling
- Validate all incoming messages
- Use consistent message format
- Handle errors gracefully

### 3. Security
- Authenticate all connections
- Validate message content
- Implement rate limiting

### 4. Performance
- Use message batching when possible
- Monitor memory usage
- Implement proper cleanup

### 5. Monitoring
- Log important events
- Track connection metrics
- Monitor error rates

## Next Steps

- [Real-time Features](real-time.md)
- [Performance Guide](performance.md)
- [Security Guide](security.md) 