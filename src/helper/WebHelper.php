<?php
namespace Gemvc\Helper;

class WebHelper
{
    /**
     * Detect the web server software and its capabilities.
     *
     * @return array<string,mixed> Returns an array with server information
     */
    public static function detectWebServer(): array
    {
        $serverInfo = [
            'name' => null,
            'version' => null,
            'swoole_available' => false,
            'openswoole_available' => false,
            'capabilities' => []
        ];

        // Check for Swoole/OpenSwoole availability
        $serverInfo['swoole_available'] = extension_loaded('swoole');
        $serverInfo['openswoole_available'] = extension_loaded('openswoole');

        // Get server software information
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;
        
        if ($serverSoftware === null) {
            // Check if running in CLI
            if (PHP_SAPI === 'cli') {
                $serverInfo['name'] = 'cli';
                if ($serverInfo['openswoole_available'] || $serverInfo['swoole_available']) {
                    $serverInfo['name'] = 'swoole';
                    $serverInfo['capabilities'] = [
                        'websockets' => true,
                        'async' => true,
                        'hot_reload' => true,
                        'static_files' => true
                    ];
                }
                return $serverInfo;
            }
            
            error_log('Web server detection failed: SERVER_SOFTWARE not available');
            return $serverInfo;
        }
        /** @phpstan-ignore-next-line */
        $serverSoftware = (string)$serverSoftware;
        // Detect server and version
        if (stripos($serverSoftware, 'apache') !== false) {
            $serverInfo['name'] = 'apache';
            preg_match('/Apache\/([\d\.]+)/i', $serverSoftware, $matches);
            $serverInfo['version'] = $matches[1] ?? null;
            $serverInfo['capabilities'] = [
                'mod_rewrite' => self::isModRewriteEnabled(),
                'htaccess' => self::isHtaccessSupported(),
                'gzip' => self::isModDeflateEnabled(),
                'ssl' => self::isModSSLEnabled()
            ];
        } elseif (stripos($serverSoftware, 'nginx') !== false) {
            $serverInfo['name'] = 'nginx';
            preg_match('/nginx\/([\d\.]+)/i', $serverSoftware, $matches);
            $serverInfo['version'] = $matches[1] ?? null;
            $serverInfo['capabilities'] = [
                'rewrite' => true,
                'gzip' => true,
                'ssl' => true
            ];
        } elseif (stripos($serverSoftware, 'swoole') !== false || 
                  $serverInfo['swoole_available'] || 
                  $serverInfo['openswoole_available']) {
            $serverInfo['name'] = 'swoole';
            $serverInfo['capabilities'] = [
                'websockets' => true,
                'async' => true,
                'hot_reload' => true,
                'static_files' => true
            ];
        }

        return $serverInfo;
    }

    /**
     * Check if mod_rewrite is enabled (Apache)
     */
    private static function isModRewriteEnabled(): bool
    {
        if (function_exists('apache_get_modules')) {
            return in_array('mod_rewrite', apache_get_modules());
        }
        return false;
    }

    /**
     * Check if .htaccess files are supported (Apache)
     */
    private static function isHtaccessSupported(): bool
    {
        return is_file('.htaccess') && !ini_get('apache.mod_php');
    }

    /**
     * Check if mod_deflate is enabled (Apache)
     */
    private static function isModDeflateEnabled(): bool
    {
        if (function_exists('apache_get_modules')) {
            return in_array('mod_deflate', apache_get_modules());
        }
        return false;
    }

    /**
     * Check if mod_ssl is enabled (Apache)
     */
    private static function isModSSLEnabled(): bool
    {
        if (function_exists('apache_get_modules')) {
            return in_array('mod_ssl', apache_get_modules());
        }
        return false;
    }
}

