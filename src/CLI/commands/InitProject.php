<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;

class InitProject extends Command
{
    private string $basePath;
    private string $packagePath;
    private bool $nonInteractive = false;
    
    public function execute()
    {
        // Check if non-interactive mode is requested
        $this->nonInteractive = in_array('--non-interactive', $this->args) || in_array('-n', $this->args);
        if ($this->nonInteractive) {
            $this->info("Running in non-interactive mode - will automatically accept defaults and overwrite files");
        }
        
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
            
            // Add composer update reminder with development and production options
            $this->write("\n\033[1;33m╭─ Next Steps ───────────────────────────────────────────────╮\033[0m\n", 'yellow');
            
            // Development Environment
            $this->write("\033[1;33m│\033[0m \033[1;94mDevelopment Environment:\033[0m                                \033[1;33m│\033[0m\n", 'white');
            $this->write("\033[1;33m│\033[0m  \033[1;36m$ \033[1;95mcomposer update\033[0m                                      \033[1;33m│\033[0m\n", 'white');
            $this->write("\033[1;33m│\033[0m    \033[90m# Includes development dependencies for testing/debugging\033[0m \033[1;33m│\033[0m\n", 'white');
            
            // Separator
            $this->write("\033[1;33m│\033[0m                                                           \033[1;33m│\033[0m\n", 'white');
            
            // Production Environment
            $this->write("\033[1;33m│\033[0m \033[1;91mProduction Environment:\033[0m                                 \033[1;33m│\033[0m\n", 'white');
            $this->write("\033[1;33m│\033[0m  \033[1;36m$ \033[1;95mcomposer update \033[1;93m--no-dev \033[1;92m--prefer-dist \033[1;96m--optimize-autoloader\033[0m \033[1;33m│\033[0m\n", 'white');
            $this->write("\033[1;33m│\033[0m    \033[90m# Optimized installation without development packages\033[0m    \033[1;33m│\033[0m\n", 'white');
            
            $this->write("\033[1;33m╰───────────────────────────────────────────────────────╯\033[0m\n\n", 'yellow');

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
        
        // Don't overwrite existing .env file without confirmation (unless in non-interactive mode)
        if (file_exists($envPath) && !$this->nonInteractive) {
            echo "File already exists: {$envPath}" . PHP_EOL;
            echo "Do you want to overwrite it? (y/N): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            
            if (strtolower(trim($line)) !== 'y') {
                $this->info("Skipped .env creation");
                return;
            }
        } elseif (file_exists($envPath) && $this->nonInteractive) {
            $this->info("File already exists (non-interactive mode): {$envPath} - will be overwritten");
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
        // First, try to find the startup template path
        $potentialPaths = [
            $this->packagePath . '/src/startup',
            $this->packagePath . '/startup',
            dirname(dirname(dirname(__DIR__))) . '/startup'
        ];
        
        $startupPath = null;
        foreach ($potentialPaths as $path) {
            if (is_dir($path)) {
                $startupPath = $path;
                $this->info("Found startup directory: {$startupPath}");
                break;
            }
        }
        
        if ($startupPath === null) {
            throw new \RuntimeException("Startup directory not found. Tried: " . implode(", ", $potentialPaths));
        }
        
        // Check for available templates
        $templateDirs = [];
        $dirs = scandir($startupPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            if (is_dir($startupPath . '/' . $dir)) {
                $templateDirs[] = $dir;
                $this->info("Found template: {$dir}");
            }
        }
        
        // For non-interactive mode, or if there's just one template, use it directly
        if (count($templateDirs) === 1 || $this->nonInteractive) {
            $templateName = $templateDirs[0] ?? null;
            
            // In non-interactive mode, prefer apache if available
            if ($this->nonInteractive && count($templateDirs) > 1) {
                if (in_array('apache', $templateDirs)) {
                    $templateName = 'apache';
                } elseif (in_array('swoole', $templateDirs)) {
                    $templateName = 'swoole';
                }
            }
            
            if ($templateName) {
                $this->info("Using template: {$templateName}");
                $templateDir = $startupPath . '/' . $templateName;
                $this->copyTemplateFiles($templateDir);
                return;
            }
        }
        
        // If we have multiple templates and we're in interactive mode, let the user choose
        if (count($templateDirs) > 1 && !$this->nonInteractive) {
            $this->write("\n\033[1;33mAvailable Templates:\033[0m\n", 'yellow');  // Bright yellow header
            foreach ($templateDirs as $index => $dir) {
                echo "  [\033[32m{$index}\033[0m] \033[1m{$dir}\033[0m\n";  // Green number, bold template name
            }
            echo "\n\033[1;36mEnter choice (number):\033[0m ";  // Bright cyan prompt
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            if (isset($templateDirs[(int)$choice])) {
                $templateName = $templateDirs[(int)$choice];
                $templateDir = $startupPath . '/' . $templateName;
                $this->info("Using template: {$templateName}");
                $this->copyTemplateFiles($templateDir);
                return;
            } else {
                throw new \RuntimeException("\033[31mInvalid template choice\033[0m");
            }
        }
        
        // If there are no template directories, try to use the startup dir itself
        if (empty($templateDirs)) {
            $this->info("No specific templates found, using startup directory directly");
            $this->copyTemplateFiles($startupPath);
        }
    }
    
    private function copyTemplateFiles($templateDir)
    {
        if (!is_dir($templateDir)) {
            throw new \RuntimeException("Template directory not found: {$templateDir}");
        }
        
        $files = scandir($templateDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourcePath = $templateDir . '/' . $file;
            $destPath = $this->basePath . '/' . $file;
            
            // Skip directories, we just want files
            if (is_dir($sourcePath)) {
                continue;
            }
            
            // Check if file already exists
            if (file_exists($destPath) && !$this->nonInteractive) {
                echo "File already exists: {$destPath}" . PHP_EOL;
                echo "Do you want to overwrite it? (y/N): ";
                $handle = fopen("php://stdin", "r");
                $line = fgets($handle);
                fclose($handle);
                
                if (strtolower(trim($line)) !== 'y') {
                    $this->info("Skipped: {$file}");
                    continue;
                }
            } elseif (file_exists($destPath) && $this->nonInteractive) {
                $this->info("File already exists (non-interactive mode): {$destPath} - will be overwritten");
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
        // Multiple possible locations for package path
        $paths = [
            // If we're in development mode
            dirname(dirname(dirname(__DIR__))),
            
            // If installed via Composer
            dirname(dirname(dirname(dirname(__DIR__)))) . '/gemvc/library',
            
            // Other common paths
            dirname(dirname(dirname(dirname(__DIR__)))) . '/gemvc/framework'
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->info("Using package path: {$path}");
                return $path;
            }
        }
        
        // Fallback
        $currentDir = dirname(dirname(dirname(__FILE__))); // src/CLI/commands -> src
        $this->warning("Using fallback package path: {$currentDir}");
        return dirname($currentDir); // Go up from src to package root
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
        
        // For Unix/Linux/Mac systems - only prompt if not in non-interactive mode
        if (!$this->nonInteractive) {
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
        } else {
            // Skip global command setup in non-interactive mode
            $this->info("Skipped global command setup (non-interactive mode)");
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
                    if (!$this->nonInteractive) {
                        echo "Command already exists at {$globalBinPath}. Overwrite? (y/N): ";
                        $handle = fopen("php://stdin", "r");
                        $line = fgets($handle);
                        fclose($handle);
                        
                        if (strtolower(trim($line)) !== 'y') {
                            $this->info("Skipped global command setup");
                            continue;
                        }
                    } else {
                        $this->info("Command already exists at {$globalBinPath} - skipping (non-interactive mode)");
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