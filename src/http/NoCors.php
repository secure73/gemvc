<?php
namespace Gemvc\Http;
/**
 * handle cross-origin requests
 * @method void NoCors()
 */
class NoCors
{
    public function __construct()
    {
    }
    /**
     * Handle cross-origin requests for Apache/Nginx
     * @method void apache()
     */
    public static function apache(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Headers: HTTP_AUTHORIZATION');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Content-Type: application/json');

        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN']) && is_string($_SERVER['HTTP_ORIGIN'])) {
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
            // you want to allow, and if so:
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }

        // Access-Control headers are received during OPTIONS requests
        if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                // may also be using PUT, PATCH, HEAD etc
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) && is_string($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }

            exit;
        }
    }

    /**
     * Handle cross-origin requests for Swoole/OpenSwoole
     * @param mixed $response Swoole response object
     * @method void swoole($response)
     * @example
     * ```php
     * $http = new Swoole\HTTP\Server("0.0.0.0", 9501);
     * $http->on('request', function ($request, $response) {
     *     NoCors::swoole($response);
     *     $response->end("Hello World");
     * });
     * $http->start();
     */
    public static function swoole($response): void
    {
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', '*');
        $response->header('Access-Control-Allow-Headers', 'HTTP_AUTHORIZATION');
        $response->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
        $response->header('Content-Type', 'application/json');

        // Allow from any origin
        if (isset($response->header['origin']) && is_string($response->header['origin'])) {
            $response->header('Access-Control-Allow-Origin', $response->header['origin']);
            $response->header('Access-Control-Allow-Credentials', 'true');
            $response->header('Access-Control-Max-Age', '86400');
        }

        // Access-Control headers for OPTIONS requests
        $requestMethod = $response->request->server['request_method'] ?? null;
        if ('OPTIONS' === $requestMethod) {
            if (isset($response->header['access-control-request-method'])) {
                $response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            }
            if (isset($response->header['access-control-request-headers']) && is_string($response->header['access-control-request-headers'])) {
                $response->header('Access-Control-Allow-Headers', $response->header['access-control-request-headers']);
            }
        }
    }
}
