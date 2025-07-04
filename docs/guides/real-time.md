# Real-time Features

## Overview

GEMVC provides comprehensive real-time communication capabilities through WebSocket technology, enabling instant messaging, live updates, and interactive features in your applications.

## WebSocket Implementation

### Core Components

#### SwooleWebSocketHandler
The main WebSocket handler class provides:
- **Connection Management**: Automatic connection tracking and cleanup
- **Channel System**: Subscribe/unsubscribe to message channels
- **Rate Limiting**: Prevent spam with configurable limits
- **Authentication**: JWT-based user authentication
- **Redis Integration**: Scalable multi-server support

### Basic Setup

#### Server Configuration
```php
<?php
require_once 'vendor/autoload.php';

use Gemvc\Http\SwooleWebSocketHandler;

// Create WebSocket handler
$handler = new SwooleWebSocketHandler([
    'connectionTimeout' => 300,
    'maxMessagesPerMinute' => 60,
    'heartbeatInterval' => 30,
    'redis' => [
        'enabled' => true,
        'host' => '127.0.0.1',
        'port' => 6379
    ]
]);

// Create server
$server = new \OpenSwoole\WebSocket\Server('0.0.0.0', 9501);

// Register event handlers
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

#### Client Connection
```javascript
// JavaScript WebSocket client
const ws = new WebSocket('ws://localhost:9501');

ws.onopen = function() {
    console.log('Connected to WebSocket server');
    
    // Subscribe to a channel
    ws.send(JSON.stringify({
        action: 'subscribe',
        data: { channel: 'chat.room.1' }
    }));
};

ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Received:', data);
    
    if (data.action === 'message') {
        displayMessage(data.message);
    }
};

ws.onclose = function() {
    console.log('Disconnected from WebSocket server');
};
```

## Real-time Features

### 1. Live Chat System

#### Server-side Chat Handler
```php
<?php

namespace App\Services;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\SwooleWebSocketHandler;

class ChatService extends ApiService
{
    private SwooleWebSocketHandler $wsHandler;
    
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->wsHandler = new SwooleWebSocketHandler();
    }
    
    public function sendMessage(): JsonResponse
    {
        // Validate request
        if (!$this->request->auth(['user'])) {
            return $this->request->returnResponse();
        }
        
        // Validate message data
        if (!$this->request->definePostSchema([
            'channel' => 'string',
            'message' => 'string'
        ])) {
            return Response::badRequest($this->request->error);
        }
        
        // Broadcast message to WebSocket clients
        $this->broadcastMessage(
            $this->request->post['channel'],
            $this->request->post['message']
        );
        
        return Response::success('Message sent successfully');
    }
    
    private function broadcastMessage(string $channel, string $message): void
    {
        // Implementation for broadcasting to WebSocket clients
        // This would integrate with your WebSocket server
    }
}
```

#### Client-side Chat Interface
```html
<!DOCTYPE html>
<html>
<head>
    <title>Live Chat</title>
</head>
<body>
    <div id="chat-container">
        <div id="messages"></div>
        <input type="text" id="message-input" placeholder="Type your message...">
        <button onclick="sendMessage()">Send</button>
    </div>
    
    <script>
        const ws = new WebSocket('ws://localhost:9501');
        const messagesDiv = document.getElementById('messages');
        const messageInput = document.getElementById('message-input');
        
        ws.onopen = function() {
            // Subscribe to chat channel
            ws.send(JSON.stringify({
                action: 'subscribe',
                data: { channel: 'chat.room.1' }
            }));
        };
        
        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            if (data.action === 'message') {
                displayMessage(data.message, data.sender);
            }
        };
        
        function sendMessage() {
            const message = messageInput.value;
            if (message.trim()) {
                ws.send(JSON.stringify({
                    action: 'message',
                    data: {
                        channel: 'chat.room.1',
                        message: message
                    }
                }));
                messageInput.value = '';
            }
        }
        
        function displayMessage(message, sender) {
            const messageElement = document.createElement('div');
            messageElement.innerHTML = `<strong>${sender}:</strong> ${message}`;
            messagesDiv.appendChild(messageElement);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        // Send message on Enter key
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>
```

### 2. Live Notifications

#### Notification Service
```php
<?php

namespace App\Services;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class NotificationService extends ApiService
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    
    public function sendNotification(): JsonResponse
    {
        // Validate request
        if (!$this->request->auth(['admin'])) {
            return $this->request->returnResponse();
        }
        
        // Validate notification data
        if (!$this->request->definePostSchema([
            'type' => 'string',
            'title' => 'string',
            'message' => 'string',
            '?recipients' => 'array'
        ])) {
            return Response::badRequest($this->request->error);
        }
        
        // Send notification to WebSocket clients
        $this->broadcastNotification([
            'type' => $this->request->post['type'],
            'title' => $this->request->post['title'],
            'message' => $this->request->post['message'],
            'timestamp' => time()
        ]);
        
        return Response::success('Notification sent successfully');
    }
    
    private function broadcastNotification(array $notification): void
    {
        // Implementation for broadcasting notifications
        // This would integrate with your WebSocket server
    }
}
```

### 3. Real-time Dashboard

#### Dashboard Updates
```php
<?php

namespace App\Services;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class DashboardService extends ApiService
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    
    public function getLiveStats(): JsonResponse
    {
        // Validate request
        if (!$this->request->auth(['admin'])) {
            return $this->request->returnResponse();
        }
        
        // Get real-time statistics
        $stats = [
            'active_users' => $this->getActiveUsers(),
            'total_orders' => $this->getTotalOrders(),
            'revenue_today' => $this->getRevenueToday(),
            'system_status' => $this->getSystemStatus()
        ];
        
        return Response::success('Live stats retrieved', $stats);
    }
    
    public function updateStats(): JsonResponse
    {
        // Broadcast updated stats to WebSocket clients
        $this->broadcastStats([
            'active_users' => $this->getActiveUsers(),
            'total_orders' => $this->getTotalOrders(),
            'revenue_today' => $this->getRevenueToday(),
            'timestamp' => time()
        ]);
        
        return Response::success('Stats updated');
    }
    
    private function getActiveUsers(): int
    {
        // Implementation to get active users count
        return 150;
    }
    
    private function getTotalOrders(): int
    {
        // Implementation to get total orders
        return 1250;
    }
    
    private function getRevenueToday(): float
    {
        // Implementation to get today's revenue
        return 15420.50;
    }
    
    private function getSystemStatus(): string
    {
        // Implementation to get system status
        return 'healthy';
    }
    
    private function broadcastStats(array $stats): void
    {
        // Implementation for broadcasting stats
        // This would integrate with your WebSocket server
    }
}
```

## Advanced Features

### 1. Presence System

#### User Presence Tracking
```php
<?php

namespace App\Services;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class PresenceService extends ApiService
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    
    public function updatePresence(): JsonResponse
    {
        // Validate request
        if (!$this->request->auth(['user'])) {
            return $this->request->returnResponse();
        }
        
        // Validate presence data
        if (!$this->request->definePostSchema([
            'status' => 'string',  // online, away, busy, offline
            '?activity' => 'string'
        ])) {
            return Response::badRequest($this->request->error);
        }
        
        // Update user presence
        $this->updateUserPresence(
            $this->request->userId(),
            $this->request->post['status'],
            $this->request->post['activity'] ?? null
        );
        
        // Broadcast presence update
        $this->broadcastPresenceUpdate([
            'user_id' => $this->request->userId(),
            'status' => $this->request->post['status'],
            'activity' => $this->request->post['activity'] ?? null,
            'timestamp' => time()
        ]);
        
        return Response::success('Presence updated');
    }
    
    public function getOnlineUsers(): JsonResponse
    {
        // Validate request
        if (!$this->request->auth(['user'])) {
            return $this->request->returnResponse();
        }
        
        $onlineUsers = $this->getOnlineUsersList();
        
        return Response::success('Online users retrieved', $onlineUsers);
    }
    
    private function updateUserPresence(int $userId, string $status, ?string $activity): void
    {
        // Implementation to update user presence in database
    }
    
    private function broadcastPresenceUpdate(array $presence): void
    {
        // Implementation for broadcasting presence updates
    }
    
    private function getOnlineUsersList(): array
    {
        // Implementation to get list of online users
        return [
            ['id' => 1, 'name' => 'John Doe', 'status' => 'online'],
            ['id' => 2, 'name' => 'Jane Smith', 'status' => 'away']
        ];
    }
}
```

### 2. Real-time Collaboration

#### Document Collaboration
```php
<?php

namespace App\Services;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class CollaborationService extends ApiService
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    
    public function joinDocument(): JsonResponse
    {
        // Validate request
        if (!$this->request->auth(['user'])) {
            return $this->request->returnResponse();
        }
        
        // Validate document data
        if (!$this->request->definePostSchema([
            'document_id' => 'string'
        ])) {
            return Response::badRequest($this->request->error);
        }
        
        $documentId = $this->request->post['document_id'];
        
        // Join document collaboration room
        $this->joinDocumentRoom($documentId, $this->request->userId());
        
        // Notify other users
        $this->notifyUserJoined($documentId, $this->request->userId());
        
        return Response::success('Joined document collaboration');
    }
    
    public function updateDocument(): JsonResponse
    {
        // Validate request
        if (!$this->request->auth(['user'])) {
            return $this->request->returnResponse();
        }
        
        // Validate update data
        if (!$this->request->definePostSchema([
            'document_id' => 'string',
            'changes' => 'array'
        ])) {
            return Response::badRequest($this->request->error);
        }
        
        $documentId = $this->request->post['document_id'];
        $changes = $this->request->post['changes'];
        
        // Apply changes to document
        $this->applyDocumentChanges($documentId, $changes);
        
        // Broadcast changes to other users
        $this->broadcastDocumentChanges($documentId, $changes, $this->request->userId());
        
        return Response::success('Document updated');
    }
    
    private function joinDocumentRoom(string $documentId, int $userId): void
    {
        // Implementation to join document collaboration room
    }
    
    private function notifyUserJoined(string $documentId, int $userId): void
    {
        // Implementation to notify other users
    }
    
    private function applyDocumentChanges(string $documentId, array $changes): void
    {
        // Implementation to apply changes to document
    }
    
    private function broadcastDocumentChanges(string $documentId, array $changes, int $userId): void
    {
        // Implementation to broadcast changes
    }
}
```

## Performance Optimization

### 1. Connection Management
```php
// Configure connection limits
$handler = new SwooleWebSocketHandler([
    'connectionTimeout' => 300,
    'maxMessagesPerMinute' => 60,
    'heartbeatInterval' => 30
]);
```

### 2. Message Batching
```php
// Batch multiple updates into single message
$batchUpdates = [
    ['type' => 'user_joined', 'user_id' => 1],
    ['type' => 'message_sent', 'message_id' => 123],
    ['type' => 'status_changed', 'user_id' => 2, 'status' => 'online']
];

$handler->broadcastBatch($batchUpdates);
```

### 3. Redis Clustering
```php
// Configure Redis for multi-server deployment
$handler = new SwooleWebSocketHandler([
    'redis' => [
        'enabled' => true,
        'host' => 'redis-cluster.example.com',
        'port' => 6379,
        'password' => 'your_password',
        'database' => 0,
        'prefix' => 'websocket:'
    ]
]);
```

## Security Considerations

### 1. Authentication
```php
// Ensure all WebSocket connections are authenticated
if (!$httpRequest->request->auth(['user', 'admin'])) {
    $connectionData['authenticated'] = false;
    // Reject connection or limit functionality
}
```

### 2. Rate Limiting
```php
// Prevent abuse with rate limiting
$handler = new SwooleWebSocketHandler([
    'maxMessagesPerMinute' => 60  // 60 messages per minute per connection
]);
```

### 3. Message Validation
```php
// Validate all incoming messages
if (!$request->definePostSchema([
    'channel' => 'string',
    'message' => 'string'
])) {
    // Reject invalid message
    return;
}
```

## Best Practices

### 1. Error Handling
- Always handle WebSocket connection errors
- Implement reconnection logic on the client
- Log connection issues for debugging

### 2. Message Format
- Use consistent JSON message format
- Include message type and timestamp
- Validate all incoming messages

### 3. Scalability
- Use Redis for multi-server deployments
- Implement proper connection cleanup
- Monitor memory usage

### 4. Monitoring
- Track active connections
- Monitor message rates
- Log important events

## Next Steps

- [WebSocket Components](../core/websocket-components.md)
- [Performance Guide](performance.md)
- [Security Guide](security.md) 