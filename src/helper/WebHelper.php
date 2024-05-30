<?php
namespace Gemvc\Helper;

class WebHelper
{
    public static function detectWebServer(): false|string
    {
        $serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
        if (strpos($serverSoftware, 'Apache') !== false) {
            return 'Apache';
        } elseif (strpos($serverSoftware, 'swoole') !== false) {
            return 'swoole';
        } elseif (strpos($serverSoftware, 'nginx') !== false) {
            return "nginx";
        }
        return false;
    }

}
