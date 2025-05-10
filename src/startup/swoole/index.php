<?php
/**
 * OpenSwoole Server Entry Point for GEMVC Framework
 *
 * This file initializes and runs the OpenSwoole HTTP server for the GEMVC framework.
 * It handles environment detection, server configuration, request handling,
 * and performance optimizations like file preloading.
 */

// Required dependencies
require_once 'vendor/autoload.php';

use App\Core\SwooleBootstrap;
use Gemvc\Http\SwooleRequest;
use Gemvc\Http\NoCors;
use Symfony\Component\Dotenv\Dotenv;

// ---------------------------
// Environment Configuration
// ---------------------------

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/app/.env');

// Development mode detection
$isDev = getenv('APP_ENV') === 'development' || !getenv('APP_ENV');
echo $isDev ? "Running in DEVELOPMENT mode\n" : "Running in PRODUCTION mode\n";

// ---------------------------
// Server Configuration
// ---------------------------

// Create OpenSwoole HTTP Server
$server = new \OpenSwoole\HTTP\Server('0.0.0.0', 9501);

// Set server configurations
$server->set([
    'worker_num' => $isDev ? 1 : 4,
    'max_request' => $isDev ? 1 : 1000,
    'enable_coroutine' => true,
    'document_root' => __DIR__,
    'enable_static_handler' => true,
    'static_handler_locations' => ['/public', '/assets'], // Only serve files from these directories
]);

// ---------------------------
// Helper Functions
// ---------------------------

/**
 * Preload application files into memory for better performance
 * 
 * @return int Number of files preloaded
 */
function preloadFiles(): int {
    echo "Preloading application files...\n";
    $directories = [
        'app/api',
        'app/controller',
        'app/model',
        'app/core',
        'app/http',
        'app/table',
        'vendor/gemvc'
    ];
    
    $count = 0;
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            echo "Directory not found: $dir\n";
            continue;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                try {
                    require_once $file->getPathname();
                    $count++;
                } catch (\Throwable $e) {
                    echo "Error loading file {$file->getPathname()}: {$e->getMessage()}\n";
                }
            }
        }
    }
    
    echo "Preloaded $count PHP files\n";
    return $count;
}

// ---------------------------
// Development Mode Features
// ---------------------------

// Store file hashes for hot reload
$fileHashes = [];

// Enable hot reload in development mode
if ($isDev) {
    // Setup a file watcher that runs every second
    \OpenSwoole\Timer::tick(1000, function() use (&$fileHashes, $server) {
        $dirs = ['app/api', 'app/controller', 'app/model', 'app/core', 'app/http', 'app/table'];
        $changed = false;
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            
            $files = glob("$dir/*.php");
            foreach ($files as $file) {
                $currentHash = md5_file($file);
                if (!isset($fileHashes[$file]) || $fileHashes[$file] !== $currentHash) {
                    echo "[Hot Reload] File changed: $file\n";
                    $fileHashes[$file] = $currentHash;
                    $changed = true;
                }
            }
        }
        
        if ($changed) {
            echo "[Hot Reload] Reloading workers...\n";
            $server->reload(); // Reload all worker processes
        }
    });
    
    // Clear opcache on worker start (if enabled)
    $server->on('workerStart', function() {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    });
}

// ---------------------------
// Worker Event Callbacks
// ---------------------------

// Worker start event
$server->on('workerStart', function($server, $workerId) {
    echo "Worker #$workerId started\n";
});

// Worker stop event (graceful shutdown)
$server->on('workerStop', function($server, $workerId) {
    echo "Worker #$workerId stopping...\n";
    // Perform any needed cleanup here
});

// ---------------------------
// Request Handling
// ---------------------------

// Handle each HTTP request
$server->on('request', function ($request, $response) {
    // Block direct access to app directory
    $path = parse_url($request->server['request_uri'], PHP_URL_PATH);
    if (preg_match('#^/app/#', $path)) {
        $response->status(403);
        $response->end(json_encode([
            'response_code' => 403,
            'message' => 'Access Forbidden',
            'data' => null
        ]));
        return;
    }
    
    try {
        // Apply CORS headers
        NoCors::NoCors();
        
        // Create GEMVC request from Swoole request
        $webserver = new SwooleRequest($request);
        
        // Process the request
        $bootstrap = new SwooleBootstrap($webserver->request);
        $jsonResponse = $bootstrap->processRequest();
        
        // Capture the response using output buffering
        ob_start();
        $jsonResponse->show();
        $jsonContent = ob_get_clean();
        
        // Set content type header
        $response->header('Content-Type', 'application/json');
        
        // Send the JSON response
        $response->end($jsonContent);
        
    } catch (\Throwable $e) {
        // Handle any exceptions
        $errorResponse = [
            'response_code' => 500,
            'message' => 'Internal Server Error',
            'service_message' => $e->getMessage(),
            'data' => null
        ];
        
        // Set content type header
        $response->header('Content-Type', 'application/json');
        
        // Send the error response
        $response->end(json_encode($errorResponse));
    }
});

// ---------------------------
// Server Startup
// ---------------------------

// Only preload files in production for better performance
if (!$isDev) {
    echo "Production mode detected. Preloading files...\n";
    preloadFiles();
} else {
    echo "Development mode detected. Skipping preload for faster development cycles.\n";
}

// Start the server
echo "Starting OpenSwoole server on 0.0.0.0:9501...\n";
$server->start();
