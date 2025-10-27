<?php

namespace Gemvc\Http;

use Gemvc\Http\Request;
// Redis class comes from the phpredis extension
// @see https://github.com/phpredis/phpredis

// Check for OpenSwoole or Swoole extension
if (!extension_loaded('openswoole') && !extension_loaded('swoole')) {
    throw new \Exception('Neither OpenSwoole nor Swoole extensions are installed. Please install one with: pecl install openswoole');
}

// Create an interface to make IDE happy
interface WebSocketServerInterface {
    /** @return mixed */
    public function tick(int $ms, callable $callback);
    /** @return mixed */
    public function push(int $fd, string $data, int $opcode = 1);
    public function isEstablished(int $fd): bool;
    /** @return mixed */
    public function close(int $fd);
}

/**
 * Class SwooleWebSocketHandler
 * Handles WebSocket connections using OpenSwoole/Swoole with advanced features:
 * - Connection tracking and management
 * - Rate limiting for messages
 * - Heartbeat mechanism
 * - Redis integration for scalability
 * 
 * Requirements:
 * - OpenSwoole or Swoole extension
 * - Redis extension (optional, for scalability)
 */
class SwooleWebSocketHandler
{
    /** @var array<int, mixed> */
    private array $connections = [];
    /** @var array<string, array<int>> */
    private array $channels = [];
    
    // Configuration settings
    private int $connectionTimeout = 300; // seconds
    private int $maxMessagesPerMinute = 60;
    private int $heartbeatInterval = 30; // seconds
    private bool $useRedis = false;
    /** @var \Redis|null */
    private $redis = null;
    private string $redisPrefix = 'websocket:';
    
    // Rate limiting tracking
    /** @var array<int, array<int, int>> */
    private array $messageCounters = [];
    
    /**
     * Constructor with optional Redis configuration for scalability
     * 
     * @param array<string, mixed> $config Configuration options
     */
    public function __construct(array $config = [])
    {
        // Set configuration values if provided
        if (isset($config['connectionTimeout'])) {
            $this->connectionTimeout = $config['connectionTimeout'];
        }
        
        if (isset($config['maxMessagesPerMinute'])) {
            $this->maxMessagesPerMinute = $config['maxMessagesPerMinute'];
        }
        
        if (isset($config['heartbeatInterval'])) {
            $this->heartbeatInterval = $config['heartbeatInterval'];
        }
        
        // Initialize Redis if configured and the extension is available
        if (isset($config['redis']) && $config['redis']['enabled'] && extension_loaded('redis')) {
            $this->initRedis($config['redis']);
        }
    }
    
    /**
     * Initialize Redis connection
     * @param array<string, mixed> $config
     */
    private function initRedis(array $config): void
    {
        try {
            if (!class_exists('Redis')) {
                throw new \Exception("Redis extension not installed");
            }
            
            $this->redis = new \Redis(); // Add backslash to use global namespace
            $this->redis->connect(
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 6379
            );
            
            if (isset($config['password']) && !empty($config['password'])) {
                $this->redis->auth($config['password']);
            }
            
            if (isset($config['database'])) {
                $this->redis->select($config['database']);
            }
            
            if (isset($config['prefix'])) {
                $this->redisPrefix = $config['prefix'];
            }
            
            $this->useRedis = true;
        } catch (\Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            $this->useRedis = false;
        }
    }
    
    /**
     * Register the heartbeat timer with the Swoole server
     * 
     * @param object $server Either OpenSwoole\WebSocket\Server or Swoole\WebSocket\Server
     */
    public function registerHeartbeat(object $server): void
    {
        $this->checkServerCompatibility($server);
        
        // @phpstan-ignore-next-line
        $server->tick($this->heartbeatInterval * 1000, function() use ($server) {
            $this->performHeartbeat($server);
        });
        
        // Also register a cleanup timer for expired connections
        // @phpstan-ignore-next-line
        $server->tick(60000, function() use ($server) {
            $this->cleanupExpiredConnections($server);
        });
    }
    
    /**
     * Handle a new WebSocket connection
     * 
     * @param object $server Either OpenSwoole\WebSocket\Server or Swoole\WebSocket\Server
     * @param object $request The HTTP request object
     */
    public function onOpen(object $server, object $request): void
    {
        $this->checkServerCompatibility($server);
        
        // Create a standard Request object for the handshake
        $httpRequest = new SwooleRequest($request);
        
        // Initialize connection data
        $connectionData = [
            'request' => $httpRequest->request,
            // @phpstan-ignore-next-line
            'ip' => $request->server['remote_addr'],
            'time' => time(),
            'last_activity' => time(),
            'channels' => [],
            'rate_limit' => [
                'message_count' => 0,
                'window_start' => time()
            ]
        ];
        
        // Authenticate if possible
        if ($httpRequest->request->auth(['user', 'admin'])) {
            $connectionData['user_id'] = $httpRequest->request->userId();
            $connectionData['role'] = $httpRequest->request->userRole();
            $connectionData['authenticated'] = true;
        } else {
            $connectionData['authenticated'] = false;
        }
        
        // Store connection info
        if ($this->useRedis) {
            // @phpstan-ignore-next-line
            $this->storeConnectionInRedis($request->fd, $connectionData);
        } else {
            // @phpstan-ignore-next-line
            $this->connections[$request->fd] = $connectionData;
        }
        
        // Send welcome message with heartbeat info
        // @phpstan-ignore-next-line
        $server->push($request->fd, json_encode([
            'action' => 'welcome',
            'heartbeat_interval' => $this->heartbeatInterval,
            'connection_id' => $request->fd,
            'authenticated' => $connectionData['authenticated']
        ]));
    }
    
    /**
     * Handle WebSocket messages
     * 
     * @param object $server Either OpenSwoole\WebSocket\Server or Swoole\WebSocket\Server
     * @param object $frame The WebSocket frame
     */
    public function onMessage(object $server, object $frame): void
    {
        $this->checkServerCompatibility($server);
        
        // Update last activity time
        // @phpstan-ignore-next-line
        $this->updateLastActivity($frame->fd);
        
        // Check rate limiting
        // @phpstan-ignore-next-line
        if (!$this->checkRateLimit($frame->fd)) {
            // @phpstan-ignore-next-line
            $server->push($frame->fd, json_encode([
                'success' => false,
                'error' => 'Rate limit exceeded. Please slow down.',
                'retry_after' => 60 // seconds
            ]));
            return;
        }
        
        // Parse message (typically JSON)
        // @phpstan-ignore-next-line
        $message = json_decode($frame->data, true);
        if (!$message || !isset($message['action'])) {
            // @phpstan-ignore-next-line
            $server->push($frame->fd, json_encode([
                'success' => false,
                'error' => 'Invalid message format'
            ]));
            return;
        }
        
        // Handle heartbeat pong response
        if ($message['action'] === 'pong') {
            return;
        }
        
        // Convert to a Request-like structure
        $socketRequest = new Request();
        $socketRequest->requestMethod = 'SOCKET';
        
        // Set data based on message type
        switch ($message['action']) {
            case 'subscribe':
                $socketRequest->post = is_array($message['data'] ?? null) ? $message['data'] : [];
                // @phpstan-ignore-next-line
                $this->handleSubscribe($server, $frame->fd, $socketRequest);
                break;
                
            case 'message':
                $socketRequest->post = is_array($message['data'] ?? null) ? $message['data'] : [];
                // @phpstan-ignore-next-line
                $this->handleMessage($server, $frame->fd, $socketRequest);
                break;
                
            case 'unsubscribe':
                $socketRequest->post = is_array($message['data'] ?? null) ? $message['data'] : [];
                $this->handleUnsubscribe($server, $frame->fd, $socketRequest);
                break;
                
            default:
                // @phpstan-ignore-next-line
                $server->push($frame->fd, json_encode([
                    'success' => false,
                    'error' => 'Unknown action: ' . (string) ($message['action'] ?? 'unknown')
                ]));
        }
    }
    
    /**
     * Handle channel subscription
     */
    private function handleSubscribe(object $server, mixed $fd, Request $request): void
    {
        // Validate the subscription request
        if ($request->definePostSchema([
            'channel' => 'string',
            '?options' => 'array'
        ])) {
            $channel = $request->post['channel'] ?? '';
            if (!is_string($channel)) {
                return;
            }
            
            // Add to channel
            if ($this->useRedis) {
                $this->subscribeChannelRedis($fd, $channel);
            } else {
                if (!isset($this->channels[$channel])) {
                    $this->channels[$channel] = [];
                }
                
                $this->channels[$channel][] = $fd;
                // @phpstan-ignore-next-line
                $this->connections[$fd]['channels'][] = $channel;
            }
            
            // Send success response
            $server->push($fd, json_encode([
                'success' => true,
                'action' => 'subscribe',
                'channel' => $channel
            ]));
        } else {
            // Send error response using Request's error handling
            $server->push($fd, json_encode([
                'success' => false,
                'error' => $request->error
            ]));
        }
    }
    
    /**
     * Handle unsubscribe request
     */
    private function handleUnsubscribe(object $server, mixed $fd, Request $request): void
    {
        // Validate the unsubscribe request
        if ($request->definePostSchema([
            'channel' => 'string'
        ])) {
            $channel = $request->post['channel'] ?? '';
            if (!is_string($channel)) {
                return;
            }
            
            if ($this->useRedis) {
                $this->unsubscribeChannelRedis($fd, $channel);
            } else {
                // Remove from local channel list
                if (isset($this->channels[$channel])) {
                    $this->channels[$channel] = array_filter(
                        $this->channels[$channel], 
                        function($client) use ($fd) { 
                            return $client !== $fd; 
                        }
                    );
                }
                
                // Remove from connection's channel list
                if (isset($this->connections[$fd])) {
                    $this->connections[$fd]['channels'] = array_filter(
                        $this->connections[$fd]['channels'],
                        function($chan) use ($channel) {
                            return $chan !== $channel;
                        }
                    );
                }
            }
            
            // Send success response
            $server->push($fd, json_encode([
                'success' => true,
                'action' => 'unsubscribe',
                'channel' => $channel
            ]));
        } else {
            // Send error response
            $server->push($fd, json_encode([
                'success' => false,
                'error' => $request->error
            ]));
        }
    }
    
    /**
     * Handle message broadcasting
     */
    private function handleMessage(object $server, mixed $fd, Request $request): void
    {
        // Validate the message
        if ($request->definePostSchema([
            'channel' => 'string',
            'message' => 'string',
            '?recipients' => 'array'
        ])) {
            $channel = $request->post['channel'] ?? '';
            $message = $request->post['message'] ?? '';
            if (!is_string($channel) || !is_string($message)) {
                return;
            }
            
            // Check if user has access to this channel
            if ($this->isSubscribedToChannel($fd, $channel)) {
                $recipients = $this->getChannelRecipients($channel);
                
                // Filter recipients if specified
                if (isset($request->post['recipients']) && !empty($request->post['recipients'])) {
                    $targetRecipients = $request->post['recipients'];
                    if (!is_array($targetRecipients)) {
                        return;
                    }
                    $recipients = array_filter($recipients, function($recipient) use ($targetRecipients) {
                        return in_array($recipient, $targetRecipients);
                    });
                }
                
                // Broadcast message
                foreach ($recipients as $recipient) {
                    $server->push($recipient, json_encode([
                        'action' => 'message',
                        'channel' => $channel,
                        'sender' => $fd,
                        'message' => $message,
                        'time' => time()
                    ]));
                }
                
                // Confirm message sent
                $server->push($fd, json_encode([
                    'success' => true,
                    'action' => 'message_sent',
                    'recipients_count' => count($recipients)
                ]));
            } else {
                $server->push($fd, json_encode([
                    'success' => false,
                    'error' => 'Not subscribed to channel'
                ]));
            }
        } else {
            // Send error response
            $server->push($fd, json_encode([
                'success' => false,
                'error' => $request->error
            ]));
        }
    }
    
    /**
     * Handle connection close
     */
    public function onClose(object $server, mixed $fd): void
    {
        if ($this->useRedis) {
            $this->removeConnectionFromRedis($fd);
        } else {
            // Remove from channels
            if (isset($this->connections[$fd])) {
                foreach ($this->connections[$fd]['channels'] as $channel) {
                    if (isset($this->channels[$channel])) {
                        $this->channels[$channel] = array_filter(
                            $this->channels[$channel], 
                            function($client) use ($fd) { 
                                return $client !== $fd; 
                            }
                        );
                    }
                }
                
                // Clean up connection
                unset($this->connections[$fd]);
                unset($this->messageCounters[$fd]);
            }
        }
    }
    
    /**
     * Perform heartbeat check on all connections
     * 
     * @param object $server Either OpenSwoole\WebSocket\Server or Swoole\WebSocket\Server
     */
    private function performHeartbeat(object $server): void
    {
        $this->checkServerCompatibility($server);
        
        $connections = $this->useRedis ? $this->getAllConnectionsFromRedis() : $this->connections;
        
        foreach ($connections as $fd => $connection) {
            // Skip if connection is already closed
            if (!$server->isEstablished($fd)) {
                continue;
            }
            
            // Send ping message
            try {
                $server->push($fd, json_encode([
                    'action' => 'ping',
                    'time' => time()
                ]));
            } catch (\Exception $e) {
                // Connection might be broken, close it
                if ($server->isEstablished($fd)) {
                    $server->close($fd);
                }
            }
        }
    }
    
    /**
     * Clean up expired connections
     * 
     * @param object $server Either OpenSwoole\WebSocket\Server or Swoole\WebSocket\Server
     */
    private function cleanupExpiredConnections(object $server): void
    {
        $this->checkServerCompatibility($server);
        
        $now = time();
        $connections = $this->useRedis ? $this->getAllConnectionsFromRedis() : $this->connections;
        
        foreach ($connections as $fd => $connection) {
            // Check if connection has timed out
            if (($now - $connection['last_activity']) > $this->connectionTimeout) {
                if ($server->isEstablished($fd)) {
                    // Send timeout message before closing
                    try {
                        $server->push($fd, json_encode([
                            'action' => 'timeout',
                            'message' => 'Connection timed out due to inactivity'
                        ]));
                        $server->close($fd);
                    } catch (\Exception $e) {
                        // Connection already broken, just close it
                        if ($server->isEstablished($fd)) {
                            $server->close($fd);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Check if rate limit is exceeded
     */
    private function checkRateLimit(int $fd): bool
    {
        $now = time();
        
        if ($this->useRedis) {
            return $this->checkRateLimitRedis($fd, $now);
        }
        
        // Initialize counter if needed
        if (!isset($this->messageCounters[$fd])) {
            $this->messageCounters[$fd] = [
                'count' => 0,
                'window_start' => $now
            ];
        }
        
        // Reset counter if window has passed
        if (($now - $this->messageCounters[$fd]['window_start']) > 60) {
            $this->messageCounters[$fd] = [
                'count' => 0,
                'window_start' => $now
            ];
        }
        
        // Increment counter
        $this->messageCounters[$fd]['count']++;
        
        // Check if limit exceeded
        if ($this->messageCounters[$fd]['count'] > $this->maxMessagesPerMinute) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Update last activity time for connection
     */
    private function updateLastActivity(int $fd): void
    {
        $now = time();
        
        if ($this->useRedis) {
            $this->updateLastActivityRedis($fd, $now);
        } else if (isset($this->connections[$fd])) {
            $this->connections[$fd]['last_activity'] = $now;
        }
    }
    
    /**
     * Check if connection is subscribed to channel
     */
    private function isSubscribedToChannel(int $fd, string $channel): bool
    {
        if ($this->useRedis) {
            return $this->isSubscribedToChannelRedis($fd, $channel);
        }
        
        return isset($this->connections[$fd]) && 
               in_array($channel, $this->connections[$fd]['channels']);
    }
    
    /**
     * Get all recipients for a channel
     */
    /**
     * @return array<int>
     */
    private function getChannelRecipients(string $channel): array
    {
        if ($this->useRedis) {
            return $this->getChannelRecipientsRedis($channel);
        }
        
        return $this->channels[$channel] ?? [];
    }
    
    /*---------------------------
     * Redis Integration Methods
     *---------------------------*/
    
    /**
     * Store connection data in Redis
     */
    /**
     * @param array<string, mixed> $data
     */
    private function storeConnectionInRedis(int $fd, array $data): void
    {
        if (!$this->redis) {
            return;
        }
        
        $key = $this->redisPrefix . 'connection:' . $fd;
        $this->redis->hMSet($key, [
            'ip' => $data['ip'],
            'time' => $data['time'],
            'last_activity' => $data['last_activity'],
            'authenticated' => $data['authenticated'] ? '1' : '0',
            'user_id' => $data['user_id'] ?? '',
            'role' => $data['role'] ?? '',
            'channels' => json_encode($data['channels'])
        ]);
        
        // Set expiration to avoid memory leaks
        $this->redis->expire($key, $this->connectionTimeout * 2);
    }
    
    /**
     * Get connection data from Redis
     */
    /**
     * @return array<string, mixed>|null
     */
    private function getConnectionFromRedis(int $fd): ?array
    {
        if (!$this->redis) {
            return null;
        }
        
        $key = $this->redisPrefix . 'connection:' . $fd;
        $data = $this->redis->hGetAll($key);
        
        if (empty($data)) {
            return null;
        }
        
        // Convert back to correct types
        $data['time'] = (int)$data['time'];
        $data['last_activity'] = (int)$data['last_activity'];
        $data['authenticated'] = $data['authenticated'] === '1';
        $data['channels'] = json_decode($data['channels'], true) ?? [];
        
        return $data;
    }
    
    /**
     * Get all connections from Redis
     */
    /**
     * @return array<string, mixed>
     */
    private function getAllConnectionsFromRedis(): array
    {
        if (!$this->redis) {
            return [];
        }
        
        $connections = [];
        $keys = $this->redis->keys($this->redisPrefix . 'connection:*');
        
        foreach ($keys as $key) {
            $fd = (int)str_replace($this->redisPrefix . 'connection:', '', $key);
            $data = $this->getConnectionFromRedis($fd);
            if ($data) {
                $connections[$fd] = $data;
            }
        }
        
        return $connections;
    }
    
    /**
     * Remove connection from Redis
     */
    private function removeConnectionFromRedis(int $fd): void
    {
        if (!$this->redis) {
            return;
        }
        
        // Get channels first to clean up
        $data = $this->getConnectionFromRedis($fd);
        if ($data && !empty($data['channels'])) {
            foreach ($data['channels'] as $channel) {
                $this->unsubscribeChannelRedis($fd, $channel);
            }
        }
        
        // Delete connection data
        $key = $this->redisPrefix . 'connection:' . $fd;
        $this->redis->del($key);
        
        // Clean up rate limit data
        $rateKey = $this->redisPrefix . 'rate:' . $fd;
        $this->redis->del($rateKey);
    }
    
    /**
     * Subscribe to channel in Redis
     */
    private function subscribeChannelRedis(int $fd, string $channel): void
    {
        if (!$this->redis) {
            return;
        }
        
        // Add FD to channel set
        $channelKey = $this->redisPrefix . 'channel:' . $channel;
        $this->redis->sAdd($channelKey, $fd);
        
        // Add channel to connection's channel list
        $data = $this->getConnectionFromRedis($fd);
        if ($data) {
            if (!in_array($channel, $data['channels'])) {
                $data['channels'][] = $channel;
                $this->storeConnectionInRedis($fd, $data);
            }
        }
    }
    
    /**
     * Unsubscribe from channel in Redis
     */
    private function unsubscribeChannelRedis(int $fd, string $channel): void
    {
        if (!$this->redis) {
            return;
        }
        
        // Remove FD from channel set
        $channelKey = $this->redisPrefix . 'channel:' . $channel;
        $this->redis->sRem($channelKey, $fd);
        
        // Remove channel from connection's channel list
        $data = $this->getConnectionFromRedis($fd);
        if ($data) {
            $data['channels'] = array_filter($data['channels'], function($c) use ($channel) {
                return $c !== $channel;
            });
            $this->storeConnectionInRedis($fd, $data);
        }
    }
    
    /**
     * Check if subscribed to channel in Redis
     */
    private function isSubscribedToChannelRedis(int $fd, string $channel): bool
    {
        if (!$this->redis) {
            return false;
        }
        
        $channelKey = $this->redisPrefix . 'channel:' . $channel;
        return $this->redis->sIsMember($channelKey, $fd);
    }
    
    /**
     * Get channel recipients from Redis
     */
    /**
     * @return array<int>
     */
    private function getChannelRecipientsRedis(string $channel): array
    {
        if (!$this->redis) {
            return [];
        }
        
        $channelKey = $this->redisPrefix . 'channel:' . $channel;
        $members = $this->redis->sMembers($channelKey);
        
        // Convert string FDs to integers
        return array_map('intval', $members);
    }
    
    /**
     * Update last activity in Redis
     */
    private function updateLastActivityRedis(int $fd, int $time): void
    {
        if (!$this->redis) {
            return;
        }
        
        $key = $this->redisPrefix . 'connection:' . $fd;
        $this->redis->hSet($key, 'last_activity', $time);
        
        // Refresh expiration
        $this->redis->expire($key, $this->connectionTimeout * 2);
    }
    
    /**
     * Check rate limit in Redis
     */
    private function checkRateLimitRedis(int $fd, int $now): bool
    {
        if (!$this->redis) {
            return true;
        }
        
        $key = $this->redisPrefix . 'rate:' . $fd;
        $windowKey = $key . ':window';
        
        // Check if window key exists
        $windowStart = $this->redis->get($windowKey);
        if (!$windowStart) {
            // Set new window
            $this->redis->set($windowKey, $now);
            $this->redis->expire($windowKey, 120); // 2 minutes (longer than window for cleanup)
            $windowStart = $now;
        }
        
        // Reset if window has passed
        if (($now - (int)$windowStart) > 60) {
            $this->redis->set($windowKey, $now);
            $this->redis->set($key, 1);
            $this->redis->expire($key, 120);
            $this->redis->expire($windowKey, 120);
            return true;
        }
        
        // Increment counter
        $count = $this->redis->incr($key);
        $this->redis->expire($key, 120);
        
        // Check if limit exceeded
        return $count <= $this->maxMessagesPerMinute;
    }

    /**
     * Helper method to check if a server object is compatible
     * 
     * @param object $server The server object to check
     * @throws \Exception If server is not compatible
     */
    private function checkServerCompatibility($server): void
    {
        // Check if this is Swoole or OpenSwoole server
        $isOpenSwoole = $server instanceof \OpenSwoole\WebSocket\Server;
        $isSwoole = $server instanceof \Swoole\WebSocket\Server;
        
        if (!$isOpenSwoole && !$isSwoole) {
            throw new \Exception('Server must be either OpenSwoole\WebSocket\Server or Swoole\WebSocket\Server');
        }
    }
}
