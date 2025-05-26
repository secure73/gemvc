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
        $server->tick(2000, function () use ($server, &$lastCheck) {
            // Only reload if files have changed
            $currentTime = time();
            if ($currentTime - $lastCheck >= 2) {  // Check every 2 seconds
                $lastCheck = $currentTime;
                $server->reload();
            }
        });
    }
    echo "Worker #$workerId started\n";
});

// Handle request
$server->on("request", function ($request, $response) {
    try {
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

function getServerConfig():array {
    return [
        'worker_num' => (int)($_ENV["SWOOLE_WORKERS"] ?? 1),    // Number of worker processes
        'daemonize' => (bool)($_ENV["SWOOLE_RUN_FOREGROUND"] ?? 0),  // Run in foreground
        'max_request' => (int)($_ENV["SWOOLE_MAX_REQUEST"] ?? 5000),  // Maximum requests per worker
        'max_conn' => (int)($_ENV["SWOOLE_MAX_CONN"] ?? 1024),  // Maximum connections
        'max_wait_time' => (int)($_ENV["SWOOLE_MAX_WAIT_TIME"] ?? 60),  // Maximum wait time for requests
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

$server->start();

