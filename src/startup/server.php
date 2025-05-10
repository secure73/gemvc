<?php
//it is a server for swoole , replace index in apache
require_once 'vendor/autoload.php';

use App\Core\SwooleBootstrap;
use Gemvc\Http\SwooleRequest;
use Gemvc\Http\NoCors;
use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/app/.env');

// Development mode detection
$isDev = getenv('APP_ENV') === 'development' || !getenv('APP_ENV');

// Create OpenSwoole HTTP Server
$server = new \OpenSwoole\HTTP\Server('0.0.0.0', 9501);

// Set server configurations
$server->set([
    'worker_num' => $isDev ? 1 : 4,
    'max_request' => $isDev ? 1 : 1000,
    'enable_coroutine' => true,
    'document_root' => __DIR__,
]);

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

// Handle each request
$server->on('request', function ($request, $response) {
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

// Start the server
$server->start();
