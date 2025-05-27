<?php
namespace Gemvc\Helper;
use Symfony\Component\Dotenv\Dotenv;

class ProjectHelper
{
    private static $rootDir = null;
    public static function rootDir(): string
    {
        if (self::$rootDir !== null) {
            return self::$rootDir;
        }

        $currentDir = __DIR__;

        while ($currentDir !== dirname($currentDir)) { // Stop at the filesystem root
            if (file_exists($currentDir . DIRECTORY_SEPARATOR . 'composer.lock')) {
                self::$rootDir = $currentDir;
                return self::$rootDir;
            }
            $currentDir = dirname($currentDir);
        }
        throw new \Exception('composer.lock not found');
    }

    public static function appDir(): string
    {
        $appDir = self::rootDir() . DIRECTORY_SEPARATOR . 'app';
        if (!file_exists($appDir)) {
            throw new \Exception('app directory not found in root directory');
        }
        return $appDir;
    }

    public static function loadEnv(): void
    {
        $envFile = self::appDir() . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($envFile)) {
            throw new \Exception('env file not found in app directory');
        } 
        $dotenv = new Dotenv();
        $dotenv->load($envFile);
    }

    
}

