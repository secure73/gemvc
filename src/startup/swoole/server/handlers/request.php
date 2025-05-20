<?php

namespace Server\Handlers;

use App\Core\SwooleBootstrap;
use Gemvc\Http\SwooleRequest;

/**
 * Register the main request handler
 * 
 * @param mixed $server The Swoole server instance
 * @return void
 */
function registerRequestHandler($server): void
{
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
            // Handle CORS headers
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Headers', '*');
            $response->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
            $response->header('Content-Type', 'application/json');

            // Handle preflight requests
            if ($request->server['request_method'] === 'OPTIONS') {
                $response->end();
                return;
            }
            
            // Create GEMVC request from Swoole request
            $webserver = new SwooleRequest($request);
            
            // Process the request
            $bootstrap = new SwooleBootstrap($webserver->request);
            $jsonResponse = $bootstrap->processRequest();
            
            // Capture the response using output buffering
            ob_start();
            $jsonResponse->show();
            $jsonContent = ob_get_clean();
            
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
            
            // Send the error response
            $response->end(json_encode($errorResponse));
        }
    });
} 