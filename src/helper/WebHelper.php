<?php
namespace Gemvc\Helper;

class WebHelper
{
    /**
     * Detect the web server software.
     *
     * @return string|null Returns the server name (Apache, Swoole, Nginx) or null if not detected.
     */
    public static function detectWebServer(): ?string
    {
        $serverSoftware = is_string($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null;
        
        if ($serverSoftware === null) {
            // Log the server detection failure (implement logging as needed)
            error_log('Web server detection failed: ' . $serverSoftware);
            return null;
        }
        $server = match (true) {
            str_contains($serverSoftware, 'Apache') => 'Apache',
            str_contains($serverSoftware, 'swoole') => 'swoole',
            str_contains($serverSoftware, 'nginx') => 'nginx',
            default => null,
        };


        return $server;
    }
}

