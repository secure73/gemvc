<?php
namespace Gemvc\Helper;
use Symfony\Component\Dotenv\Dotenv;

class ProjectHelper
{
    private static ?string $rootDir = null;
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
        $dotenv = new Dotenv();
        
        // Try root directory first
        $rootEnvFile = self::rootDir() . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($rootEnvFile)) {
            $dotenv->load($rootEnvFile);
            return;
        }
        
        // Try app directory
        $appEnvFile = self::appDir() . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($appEnvFile)) {
            $dotenv->load($appEnvFile);
            return;
        }
        
        throw new \Exception('No .env file found in root or app directory');
    }
}

