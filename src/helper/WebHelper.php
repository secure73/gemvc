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
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';

        $server = match (true) {
            str_contains($serverSoftware, 'Apache') => 'Apache',
            str_contains($serverSoftware, 'swoole') => 'swoole',
            str_contains($serverSoftware, 'nginx') => 'nginx',
            default => null,
        };

        if ($server === null) {
            // Log the server detection failure (implement logging as needed)
            error_log('Web server detection failed: ' . $serverSoftware);
        }

        return $server;
    }
}

