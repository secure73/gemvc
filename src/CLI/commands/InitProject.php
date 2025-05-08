<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;

class InitProject extends Command
{
    private string $basePath;
    private string $packagePath;
    
    public function execute()
    {
        // Determine the project root
        $this->basePath = defined('PROJECT_ROOT') ? PROJECT_ROOT : $this->determineProjectRoot();
        
        // Determine package path (where the startup templates are located)
        $this->packagePath = $this->determinePackagePath();
        
        try {
            // Create directory structure
            $this->createDirectories();
            
            // Create sample .env file
            $this->createEnvFile();
            
            // Copy startup files to project root
            $this->copyStartupFiles();
            
            $this->success("GEMVC project initialized successfully!");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    private function createDirectories()
    {
        $directories = [
            $this->basePath . '/app',
            $this->basePath . '/app/api',
            $this->basePath . '/app/controller',
            $this->basePath . '/app/model',
            $this->basePath . '/app/table'
        ];
        
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                if (!@mkdir($directory, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$directory}");
                }
                $this->info("Created directory: {$directory}");
            } else {
                $this->info("Directory already exists: {$directory}");
            }
        }
    }
    
    private function createEnvFile()
    {
        $envPath = $this->basePath . '/app/.env';
        
        // Don't overwrite existing .env file without confirmation
        if (file_exists($envPath)) {
            echo "File already exists: {$envPath}" . PHP_EOL;
            echo "Do you want to overwrite it? (y/N): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            
            if (strtolower(trim($line)) !== 'y') {
                $this->info("Skipped .env creation");
                return;
            }
        }
        
        $envContent = <<<EOT
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_db
DB_CHARSET=utf8mb4
DB_USER=root
DB_PASSWORD=''
QUERY_LIMIT=10

# Database Connection Pool
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=10
DB_CONNECTION_MAX_AGE=3600

# Security Settings
TOKEN_SECRET='your_secret'
TOKEN_ISSUER='your_api'
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
ACCESS_TOKEN_VALIDATION_IN_SECONDS=15800

# URL Configuration
SERVICE_IN_URL_SECTION=2
METHOD_IN_URL_SECTION=3

# OpenSwoole Configuration (optional)
SWOOLE_MODE=false
OPENSWOOLE_WORKERS=3

# WebSocket Settings (optional)
WS_CONNECTION_TIMEOUT=300
WS_MAX_MESSAGES_PER_MINUTE=60
WS_HEARTBEAT_INTERVAL=30
EOT;
        
        if (!file_put_contents($envPath, $envContent)) {
            throw new \RuntimeException("Failed to create .env file: {$envPath}");
        }
        
        $this->info("Created .env file: {$envPath}");
    }
    
    private function copyStartupFiles()
    {
        $startupPath = $this->packagePath . '/startup';
        
        if (!is_dir($startupPath)) {
            throw new \RuntimeException("Startup directory not found: {$startupPath}");
        }
        
        $files = scandir($startupPath);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourcePath = $startupPath . '/' . $file;
            $destPath = $this->basePath . '/' . $file;
            
            // Skip directories, we just want files
            if (is_dir($sourcePath)) {
                continue;
            }
            
            // Check if file already exists
            if (file_exists($destPath)) {
                echo "File already exists: {$destPath}" . PHP_EOL;
                echo "Do you want to overwrite it? (y/N): ";
                $handle = fopen("php://stdin", "r");
                $line = fgets($handle);
                fclose($handle);
                
                if (strtolower(trim($line)) !== 'y') {
                    $this->info("Skipped: {$file}");
                    continue;
                }
            }
            
            // Copy the file
            if (!copy($sourcePath, $destPath)) {
                throw new \RuntimeException("Failed to copy file: {$sourcePath} to {$destPath}");
            }
            
            $this->info("Copied: {$file}");
        }
    }
    
    private function determineProjectRoot(): string
    {
        // Start with composer's vendor directory (where this file is located)
        $vendorDir = dirname(dirname(dirname(dirname(__DIR__))));
        
        // If we're in the vendor directory, the project root is one level up
        if (basename($vendorDir) === 'vendor') {
            return dirname($vendorDir);
        }
        
        // Fallback to current directory if we can't determine project root
        return getcwd() ?: '.';
    }
    
    private function determinePackagePath(): string
    {
        // Try to locate package directory based on the location of this file
        $path = dirname(dirname(dirname(__FILE__))); // src/CLI/commands -> src
        
        if (basename($path) === 'src') {
            return dirname($path); // Go up from src to package root
        }
        
        // Try to find it within vendor directory
        $vendorPath = dirname(dirname(dirname(dirname(__FILE__)))); // src/CLI/commands -> vendor/gemvc/framework
        if (file_exists($vendorPath . '/gemvc/framework/src/startup')) {
            return $vendorPath . '/gemvc/framework';
        }
        
        throw new \RuntimeException("Could not determine package path");
    }
} 