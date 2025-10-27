<?php

namespace Gemvc\Core;

use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;
use Gemvc\Core\WebService;

class Bootstrap
{
    private Request $request;
    private string $requested_service;
    private string $requested_method;
    private bool $is_web = false;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->setRequestedService();
        $this->runApp();
    }

    private function runApp(): void
    {
        if ($this->is_web) {
            $this->handleWebRequest();
        } else {
            $this->handleApiRequest();
        }
    }

    private function handleApiRequest(): void
    {
        if (!file_exists('./app/api/'.$this->requested_service.'.php')) {
            $this->showNotFound("The API service '$this->requested_service' does not exist");
            die;
        }
        
        $serviceInstance = false;
        try {
            $service = 'App\\Api\\' . $this->requested_service;
            $serviceInstance = new $service($this->request);
        } catch (\Throwable $e) {
            $this->showNotFound($e->getMessage());
            die;
        }
        
        if (!method_exists($serviceInstance, $this->requested_method)) {
            $this->showNotFound("API method '$this->requested_method' does not exist in service '$this->requested_service'");
            die;
        }
        
        $method = $this->requested_method;
        $response = $serviceInstance->$method();
        
        // Handle different response types (JsonResponse, HtmlResponse, etc.)
        if ($response instanceof JsonResponse) {
            $response->show();
        } elseif ($response instanceof \Gemvc\Http\HtmlResponse) {
            // For Apache/Nginx, use the show() method
            $response->show();
        } elseif (method_exists($response, 'show')) {
            // For any response with a show() method
            $response->show();
        } else {
            Response::internalError("API method '$method' does not provide a valid Response as return value")->show();
        }
        
        die;
    }

    private function handleWebRequest(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        try {
            // Load the appropriate web service class
            $serviceClass = 'App\\Web\\' . $this->requested_service;
            
            // If class doesn't exist, try the default controller
            if (!class_exists($serviceClass)) {
                // Check if we're looking for a static page
                $staticPath = './app/web/pages/' . strtolower($this->requested_service) . '.php';
                if (file_exists($staticPath)) {
                    include $staticPath;
                    die;
                }
                
                // If no static page, show 404
                $this->showWebNotFound();
                die;
            }
            
            // Create controller instance
            $serviceInstance = new $serviceClass($this->request);
            
            // Verify it's a web service
            // @phpstan-ignore-next-line
            if (!($serviceInstance instanceof WebService)) {
                throw new \Exception("Web controller must extend WebService class");
            }
            
            // Check if the requested method exists
            $method = $this->requested_method ?: 'index';
            if (!method_exists($serviceInstance, $method)) {
                // Try using the method as a parameter to the index method
                if (method_exists($serviceInstance, 'index')) {
                    // @phpstan-ignore-next-line
                    $this->request->params['action'] = $method;
                    $method = 'index';
                } else {
                    $this->showWebNotFound();
                    die;
                }
            }

            $serviceInstance->$method();
            
        } catch (\Throwable $e) {
            // Handle errors
            http_response_code(500);
            
            echo '<!DOCTYPE html>
                <html>
                <head>
                    <title>500 - Server Error</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                        h1 { font-size: 36px; color: #333; }
                        p { font-size: 18px; color: #666; }
                        .error { color: #cc0000; text-align: left; margin: 20px auto; max-width: 800px; }
                        pre { text-align: left; background: #f8f8f8; padding: 15px; border-radius: 5px; overflow: auto; }
                    </style>
                </head>
                <body>
                    <h1>500 - Server Error</h1>
                    <p>An error occurred while processing your request.</p>';
            
            if ($_ENV['DEBUG'] ?? false) {
                echo '<div class="error">';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '</div>';
            }
            
            echo '</body></html>';
        }
        
        die;
    }

    private function setRequestedService(): void
    {
        $method = "index";
        $segments = explode('/', $this->request->requestedUrl);
        
        // Get the first segment (service indicator)
        $serviceIndex = is_numeric($_ENV["SERVICE_IN_URL_SECTION"] ?? 1) ? (int) ($_ENV["SERVICE_IN_URL_SECTION"] ?? 1) : 1;
        $service = isset($segments[$serviceIndex]) ? 
            strtolower($segments[$serviceIndex]) : "";
            
        // Check if this is an API request
        if ($service === "api") {
            $this->is_web = false;
            
            // For API requests, get the actual service name from the next segment
            if (isset($segments[$serviceIndex + 1]) && $segments[$serviceIndex + 1]) {
                $service = ucfirst($segments[$serviceIndex + 1]);
            } else {
                $service = "Index";
            }
            
            // Get the method for API
            if (isset($segments[$serviceIndex + 2]) && $segments[$serviceIndex + 2]) {
                $method = $segments[$serviceIndex + 2];
            }
        } else {
            // Default to web
            $this->is_web = true;
            
            // For web requests, map the URL path to service/method
            if (empty($service)) {
                // Root URL - use home controller
                $service = "Home";
            } else {
                // Capitalize the service name
                $service = ucfirst($service);
            }
            
            // Get the method for web pages
            if (isset($segments[$serviceIndex + 1]) && $segments[$serviceIndex + 1]) {
                $method = $segments[$serviceIndex + 1];
            }
        }
        
        $this->requested_service = $service;
        $this->requested_method = $method;
    }

    private function showNotFound(string $message): void
    {
        $jsonResponse = new JsonResponse();
        $jsonResponse->notFound($message);
        $jsonResponse->show();
    }

    private function showWebNotFound(): void
    {
        header('HTTP/1.0 404 Not Found');
        
        // Check if a custom 404 page exists
        if (file_exists('./app/web/Error/404.php')) {
            // @phpstan-ignore-next-line
            include './app/web/Error/404.php';
        } else {
            echo '<!DOCTYPE html>
                <html>
                <head>
                    <title>404 - Page Not Found</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                        h1 { font-size: 36px; color: #333; }
                        p { font-size: 18px; color: #666; }
                    </style>
                </head>
                <body>
                    <h1>404 - Page Not Found</h1>
                    <p>The page you are looking for does not exist.</p>
                </body>
                </html>';
        }
    }
}
