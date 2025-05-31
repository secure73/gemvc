<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;

class Setup extends Command
{
    private string $basePath;
    private string $packagePath;
    private string $platform;
    
    public function execute()
    {
        // Get platform from arguments
        $this->platform = $this->args[0] ?? '';
        
        if (!in_array($this->platform, ['apache', 'swoole'])) {
            $this->error("Invalid platform. Use 'gemvc setup apache' or 'gemvc setup swoole'");
            return;
        }
        
        // Determine the project root
        $this->basePath = defined('PROJECT_ROOT') ? PROJECT_ROOT : $this->determineProjectRoot();
        
        // Determine package path (where the startup templates are located)
        $this->packagePath = $this->determinePackagePath();
        
        try {
            // Copy platform-specific files
            $this->copyPlatformFiles();
            
            // Update .env file with platform settings
            $this->updateEnvFile();
            
            $this->success("GEMVC project configured for {$this->platform} successfully!");
            
            // Display next steps
            $this->showNextSteps();
            
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    private function copyPlatformFiles()
    {
        $sourcePath = $this->packagePath . '/startup/' . $this->platform;
        
        if (!is_dir($sourcePath)) {
            throw new \RuntimeException("Platform directory not found: {$sourcePath}");
        }
        
        $this->info("Copying {$this->platform} configuration files...");
        
        $files = scandir($sourcePath);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourceFilePath = $sourcePath . '/' . $file;
            $destFilePath = $this->basePath . '/' . $file;
            
            // For directories, copy recursively
            if (is_dir($sourceFilePath)) {
                $this->copyDirectory($sourceFilePath, $destFilePath);
                continue;
            }
            
            // Check if file already exists
            if (file_exists($destFilePath)) {
                echo "File already exists: {$destFilePath}" . PHP_EOL;
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
            if (!copy($sourceFilePath, $destFilePath)) {
                throw new \RuntimeException("Failed to copy file: {$sourceFilePath} to {$destFilePath}");
            }
            
            $this->info("Copied: {$file}");
        }
    }
    
    private function copyDirectory($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        
        $files = scandir($source);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourceFilePath = $source . '/' . $file;
            $destFilePath = $dest . '/' . $file;
            
            if (is_dir($sourceFilePath)) {
                $this->copyDirectory($sourceFilePath, $destFilePath);
            } else {
                copy($sourceFilePath, $destFilePath);
                $this->info("Copied: {$sourceFilePath} -> {$destFilePath}");
            }
        }
    }
    
    private function updateEnvFile()
    {
        $envPath = $this->basePath . '/app/.env';
        
        if (!file_exists($envPath)) {
            $this->warning(".env file not found at {$envPath}. Please run 'gemvc init' first.");
            return;
        }
        
        $content = file_get_contents($envPath);
        
        // Update or add SWOOLE_MODE setting
        $swooleMode = $this->platform === 'swoole' ? 'true' : 'false';
        
        if (preg_match('/SWOOLE_MODE\s*=\s*.*/', $content)) {
            // Replace existing setting
            $content = preg_replace('/SWOOLE_MODE\s*=\s*.*/', "SWOOLE_MODE={$swooleMode}", $content);
        } else {
            // Add setting if not present
            $content .= "\n# Platform Setting\nSWOOLE_MODE={$swooleMode}\n";
        }
        
        // Write updated content
        file_put_contents($envPath, $content);
        $this->info("Updated .env file with platform settings");
    }
    
    private function showNextSteps()
    {
        $this->write("\n=== Next Steps ===\n", 'blue');
        
        if ($this->platform === 'apache') {
            $this->write("1. Configure your Apache server to point to your project root\n", 'white');
            $this->write("2. Make sure mod_rewrite is enabled\n", 'white');
            $this->write("3. Ensure the .htaccess file is properly configured\n", 'white');
            $this->write("\nStart your Apache server and visit your project URL to get started!\n", 'green');
        } else {
            $this->write("1. Make sure OpenSwoole extension is installed\n", 'white');
            $this->write("   Install with: pecl install openswoole\n", 'white');
            $this->write("2. Start the Swoole server with: php index.php\n", 'white');
            $this->write("3. For production, consider using Docker with the provided config\n", 'white');
            $this->write("\nVisit http://localhost:9501 to access your API!\n", 'green');
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