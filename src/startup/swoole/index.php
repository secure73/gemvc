<?php

use Gemvc\Core\SwooleBootstrap;
use Gemvc\Http\SwooleRequest;
// Minimal OpenSwoole HTTP server

if (!extension_loaded('openswoole')) {
    die("OpenSwoole extension is not installed.\n");
}

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/app/.env');

$server = new OpenSwoole\HTTP\Server(serverHost(), serverPort());

// Configure server settings
$server->set(getServerConfig());

// Handle server start
$server->on("start", function ($server) {
    echo "OpenSwoole server started at ".serverHost().":".serverPort()."\n";
});

// Handle worker start
$server->on("workerStart", function ($server, $workerId) {
    if ($workerId === 0 && isDev()) {  // Only in first worker AND in dev environment
        $lastCheck = time();
        $lastFileHash = getFileHash(); // Get initial file hash
        
        $server->tick(15000, function () use ($server, &$lastCheck, &$lastFileHash) {
            $currentTime = time();
            $currentFileHash = getFileHash();
            
            // Only reload if files have changed AND enough time has passed
            if ($currentFileHash !== $lastFileHash && ($currentTime - $lastCheck) >= 10) {
                $lastCheck = $currentTime;
                $lastFileHash = $currentFileHash;
                echo "File changes detected, reloading server...\n";
                $server->reload();
            }
        });
    }
    echo "Worker #$workerId started\n";
});

// Handle request
$server->on("request", function ($request, $response) {
    try {
        // Security check - block direct access to sensitive directories and files
        if (!isRequestAllowed($request->server['request_uri'] ?? '')) {
            sendSecurityResponse($response);
            return;
        }
        
        $sr = new SwooleRequest($request);
        $bs = new SwooleBootstrap($sr->request);
        $result = $bs->processRequest();
        
        if ($result instanceof \Gemvc\Http\JsonResponse) {
            $result->showSwoole($response);
        } elseif ($result instanceof \Gemvc\Http\HtmlResponse) {
            $result->showSwoole($response);
        }
    } catch (\Throwable $e) {
        // Log the error
        error_log("Error processing request: " . $e->getMessage());
        
        // Send error response
        $response->status(500);
        $response->header('Content-Type', 'application/json');
        $response->end(json_encode([
            'error' => 'Internal Server Error',
            'message' => $e->getMessage()
        ]));
    }
});

// Handle worker error
$server->on("workerError", function ($server, $workerId, $workerPid, $exitCode, $signal) {
    error_log("Worker #$workerId crashed. Exit code: $exitCode, Signal: $signal");
});

/**
 * Security function to check if request is allowed
 * Allow API routes but block direct access to sensitive directories
 */
function isRequestAllowed(string $requestUri): bool {
    // Remove query parameters and normalize path
    $path = strtok($requestUri, '?');
    $path = rtrim($path, '/');
    
    // Allow root path
    if ($path === '' || $path === '/') {
        return true;
    }
    
    // Block direct access to sensitive directories
    $blockedPaths = [
        '/app',
        '/vendor', 
        '/bin',
        '/templates',
        '/config',
        '/logs',
        '/storage',
        '/.env',
        '/.git'
    ];
    
    foreach ($blockedPaths as $blockedPath) {
        if (strpos($path, $blockedPath) === 0) {
            error_log("Security: Blocked direct access to: $path");
            return false;
        }
    }
    
    // Block direct file access (files with extensions)
    $blockedExtensions = [
        '.php', '.env', '.ini', '.conf', '.config', 
        '.log', '.sql', '.db', '.sqlite', '.md', 
        '.txt', '.json', '.xml', '.yml', '.yaml'
    ];
    
    foreach ($blockedExtensions as $ext) {
        if (str_ends_with(strtolower($path), $ext)) {
            error_log("Security: Blocked file access: $path");
            return false;
        }
    }
    
    // Allow all other requests (API endpoints, routes)
    return true;
}

/**
 * Send security response for blocked requests
 */
function sendSecurityResponse($response): void {
    $response->status(403);
    $response->header('Content-Type', 'application/json');
    $response->end(json_encode([
        'error' => 'Access Denied',
        'message' => 'Direct file access is not permitted'
    ]));
}

function getServerConfig():array {
    return [
        'worker_num' => (int)($_ENV["SWOOLE_WORKERS"] ?? 1),    // Number of worker processes
        'daemonize' => (bool)($_ENV["SWOOLE_RUN_FOREGROUND"] ?? 0),  // Run in foreground
        'max_request' => (int)($_ENV["SWOOLE_MAX_REQUEST"] ?? 5000),  // Maximum requests per worker
        'max_conn' => (int)($_ENV["SWOOLE_MAX_CONN"] ?? 1024),  // Maximum connections
        'max_wait_time' => (int)($_ENV["SWOOLE_MAX_WAIT_TIME"] ?? 120),  // Maximum wait time for requests
        'enable_coroutine' => (bool)($_ENV["SWOOLE_ENABLE_COROUTINE"] ?? 1),  // Enable coroutine support
        'max_coroutine' => (int)($_ENV["SWOOLE_MAX_COROUTINE"] ?? 3000),  // Maximum number of coroutines
        'display_errors' => (int)($_ENV["SWOOLE_DISPLAY_ERRORS"] ?? 1),  // Display errors
        'heartbeat_idle_time' => (int)($_ENV["SWOOLE_HEARTBEAT_IDLE_TIME"] ?? 600),  // Connection idle timeout
        'heartbeat_check_interval' => (int)($_ENV["SWOOLE__HEARTBEAT_INTERVAL"] ?? 60),  // Heartbeat check interval
        'log_level' => (int)(($_ENV["SWOOLE_SERVER_LOG_INFO"] ?? 0) ? SWOOLE_LOG_INFO : SWOOLE_LOG_ERROR),  // Log level
        'reload_async' => true
    ];
}

function isDev():bool {
    if(isset($_ENV["APP_ENV"])){
        return $_ENV["APP_ENV"] === "dev";
    }
    return false;
}
function serverPort():int {
    return (int)($_ENV["SWOOLE_SERVER_PORT"] ?? 9501);
}
function serverHost():string {
    return $_ENV["SWOOLE_SERVER_HOST"] ?? "0.0.0.0";
}

// Helper function to get hash of all PHP files in the project
function getFileHash() {
    $files = [];
    $dirs = ['app', 'vendor/gemvc/library/src'];
    
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname() . ':' . $file->getMTime();
                }
            }
        }
    }
    
    return md5(implode('|', $files));
}

$server->start();

