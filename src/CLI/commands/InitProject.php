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
            
            // Create global command wrapper
            $this->createGlobalCommand();
            
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
        
        // Create bin directory if it doesn't exist
        if (!is_dir($this->basePath . '/bin')) {
            if (!@mkdir($this->basePath . '/bin', 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$this->basePath}/bin");
            }
            $this->info("Created directory: {$this->basePath}/bin");
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
    
    /**
     * Create a global command wrapper
     */
    private function createGlobalCommand()
    {
        $this->info("Setting up global command...");
        
        // Create a local wrapper script in the project's bin directory
        $wrapperPath = $this->basePath . '/bin/gemvc';
        $vendorBinPath = './vendor/bin/gemvc';
        
        $wrapperContent = <<<EOT
#!/usr/bin/env php
<?php
// Forward to the vendor binary
require __DIR__ . '/../vendor/bin/gemvc';
EOT;
        
        if (!file_put_contents($wrapperPath, $wrapperContent)) {
            $this->warning("Failed to create local wrapper script: {$wrapperPath}");
            return;
        }
        
        // Make it executable
        chmod($wrapperPath, 0755);
        $this->info("Created local command wrapper: {$wrapperPath}");
        
        // Create Windows batch file
        $batPath = $this->basePath . '/bin/gemvc.bat';
        $batContent = <<<EOT
@echo off
php "%~dp0gemvc" %*
EOT;
        
        if (file_put_contents($batPath, $batContent)) {
            $this->info("Created Windows batch file: {$batPath}");
        }
        
        // On Windows, suggest adding the bin directory to PATH
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->write("\nFor global access on Windows:\n", 'blue');
            $this->write("  1. Add this directory to your PATH: " . realpath($this->basePath . '/bin') . "\n", 'white');
            $this->write("  2. Then you can run 'gemvc' from any location\n\n", 'white');
            return;
        }
        
        // For Unix/Linux/Mac systems
        // Ask if the user wants to create a global symlink
        echo "Would you like to create a global 'gemvc' command? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (strtolower(trim($line)) !== 'y') {
            $this->info("Skipped global command setup");
            $this->write("\nYou can still use the command with:\n", 'blue');
            $this->write("  php vendor/bin/gemvc [command]\n", 'white');
            $this->write("  OR\n", 'white');
            $this->write("  php bin/gemvc [command]\n\n", 'white');
            return;
        }
        
        // Try to create a global symlink
        $success = false;
        $globalPaths = ['/usr/local/bin', '/usr/bin', getenv('HOME') . '/.local/bin'];
        
        foreach ($globalPaths as $globalPath) {
            if (is_dir($globalPath) && is_writable($globalPath)) {
                $globalBinPath = $globalPath . '/gemvc';
                
                // Check if it already exists
                if (file_exists($globalBinPath)) {
                    echo "Command already exists at {$globalBinPath}. Overwrite? (y/N): ";
                    $handle = fopen("php://stdin", "r");
                    $line = fgets($handle);
                    fclose($handle);
                    
                    if (strtolower(trim($line)) !== 'y') {
                        $this->info("Skipped global command setup");
                        continue;
                    }
                    
                    // Remove existing symlink or file
                    @unlink($globalBinPath);
                }
                
                // Create the symlink
                try {
                    $realPath = realpath($wrapperPath);
                    if (symlink($realPath, $globalBinPath)) {
                        $this->success("Created global command: {$globalBinPath}");
                        $success = true;
                        break;
                    }
                } catch (\Exception $e) {
                    // Continue to next path
                }
            }
        }
        
        if (!$success) {
            $this->warning("Could not create global command. You may need root privileges.");
            $this->write("\nManual setup: \n", 'blue');
            $this->write("  1. Run: sudo ln -s " . realpath($wrapperPath) . " /usr/local/bin/gemvc\n", 'white');
            $this->write("  2. Make it executable: sudo chmod +x /usr/local/bin/gemvc\n\n", 'white');
        }
    }
} 