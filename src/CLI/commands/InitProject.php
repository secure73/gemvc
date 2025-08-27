<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;

class InitProject extends Command
{
    private string $basePath;
    private string $packagePath;
    private bool $nonInteractive = false;
    private ?string $templateName = null;
    
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
            
            // Copy templates folder to project root
            $this->copyTemplatesFolder();
            
            // Copy startup files to project root (this will set the template name)
            $this->copyStartupFiles();
            
            // Create sample .env file (now we know which template was selected)
            $this->createEnvFile();
            
            // Create global command wrapper
            $this->createGlobalCommand();

            $this->write("\033[1;33m╭─ Next Steps ───────────────────────────────────────────────╮\033[0m\n", 'yellow');
            
            // Development Environment
            $this->write("\033[1;33m│\033[0m \033[1;94mDevelopment Environment:\033[0m                                  \033[1;33m│\033[0m\n", 'white');
            $this->write("\033[1;33m│\033[0m  \033[1;36m$ \033[1;95mcomposer update\033[0m                                        \033[1;33m│\033[0m\n", 'white');
            $this->write("\033[1;33m│\033[0m    \033[90m# Includes development dependencies for testing/debugging\033[0m   \033[1;33m│\033[0m\n", 'white');
            
            // Separator
            $this->write("\033[1;33m│\033[0m                                                             \033[1;33m│\033[0m\n", 'white');
            
            // Production Environment
            $this->write("\033[1;33m│\033[0m \033[1;91mProduction Environment:\033[0m                                   \033[1;33m│\033[0m\n", 'white');
            $this->write("\033[1;33m│\033[0m  \033[1;36m$ \033[1;95mcomposer update \033[1;93m--no-dev \033[1;92m--prefer-dist \033[1;96m--optimize-autoloader\033[0m \033[1;33m│\033[0m\n", 'white');
            $this->write("\033[1;33m│\033[0m    \033[90m# Optimized installation without development packages\033[0m      \033[1;33m│\033[0m\n", 'white');
            
            $this->write("\033[1;33m╰───────────────────────────────────────────────────────╯\033[0m\n\n", 'yellow');

            // Windows PATH instructions (if on Windows)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $this->write("\nFor global access on Windows:\n", 'blue');
                $this->write("  1. Add this directory to your PATH: " . realpath($this->basePath . '/bin') . "\n", 'white');
                $this->write("  2. Then you can run 'gemvc' from any location\n\n", 'white');
            }

            // Offer PHPStan installation
            $this->offerPhpstanInstallation();

            // Offer testing framework installation
            $this->offerTestingFrameworkInstallation();

            $this->success("GEMVC project initialized successfully!", true);

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

        // Copy README.md to project root
        $this->copyReadmeToRoot();
    }

    private function copyReadmeToRoot()
    {
        $sourceReadme = $this->packagePath . '/src/startup/README.md';
        $targetReadme = $this->basePath . '/README.md';
        
        if (!file_exists($sourceReadme)) {
            throw new \RuntimeException("Source README.md not found: {$sourceReadme}");
        }
        
        if (!copy($sourceReadme, $targetReadme)) {
            throw new \RuntimeException("Failed to copy README.md to project root");
        }
        
        $this->info("Copied README.md to project root");
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

        // Get the template name (apache or swoole)
        $templateName = $this->templateName ?? 'apache';
        
        // Use the example.env from the selected template
        $exampleEnvPath = $this->packagePath . '/src/startup/' . $templateName . '/example.env';
        
        if (!file_exists($exampleEnvPath)) {
            $this->error("Could not find example.env file for template: {$templateName}");
            $this->error("Expected path: {$exampleEnvPath}");
            throw new \RuntimeException("Example .env file not found for template: {$templateName}");
        }
        
        $this->info("Using example.env from {$templateName} template: {$exampleEnvPath}");
        
        $envContent = file_get_contents($exampleEnvPath);
        if ($envContent === false) {
            throw new \RuntimeException("Failed to read example .env file: {$exampleEnvPath}");
        }
        
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
            if ($dir === '.' || $dir === '..' || $dir === 'user') continue; // Exclude 'user' directory from template options
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
                $this->templateName = $templateName;  // Store the template name
                $templateDir = $startupPath . '/' . $templateName;
                $this->copyTemplateFiles($templateDir);
                
                // Copy user files after template files
                $this->copyUserFiles($startupPath);
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
                $this->templateName = $templateName;  // Store the template name
                $templateDir = $startupPath . '/' . $templateName;
                $this->info("Using template: {$templateName}");
                $this->copyTemplateFiles($templateDir);
                
                // Copy user files after template files
                $this->copyUserFiles($startupPath);
                return;
            } else {
                throw new \RuntimeException("\033[31mInvalid template choice\033[0m");
            }
        }
        
        // If there are no template directories, try to use the startup dir itself
        if (empty($templateDirs)) {
            $this->info("No specific templates found, using startup directory directly");
            $this->templateName = 'default';  // Store default template name
            $this->copyTemplateFiles($startupPath);
            
            // Copy user files after template files
            $this->copyUserFiles($startupPath);
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
            
            // Special handling for Swoole's appIndex.php
            if ($file === 'appIndex.php' && strpos($templateDir, 'swoole') !== false) {
                $destPath = $this->basePath . '/app/api/index.php';
                
                // Create app/api directory if it doesn't exist
                if (!is_dir(dirname($destPath))) {
                    mkdir(dirname($destPath), 0755, true);
                }
            }
            
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
            
            $this->info("Copied: {$file}" . ($file === 'appIndex.php' ? " to app/api/index.php" : ""));
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
php "%~dp0..\vendor\bin\gemvc" %*
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

    private function copyTemplatesFolder()
    {
        $sourceTemplatesPath = $this->packagePath . '/src/CLI/templates';
        $targetTemplatesPath = $this->basePath . '/templates';
        
        // Check if source templates directory exists
        if (!is_dir($sourceTemplatesPath)) {
            $this->warning("Templates directory not found: {$sourceTemplatesPath}");
            return;
        }
        
        // Create target templates directory if it doesn't exist
        if (!is_dir($targetTemplatesPath)) {
            if (!@mkdir($targetTemplatesPath, 0755, true)) {
                throw new \RuntimeException("Failed to create templates directory: {$targetTemplatesPath}");
            }
            $this->info("Created templates directory: {$targetTemplatesPath}");
        } else {
            $this->info("Templates directory already exists: {$targetTemplatesPath}");
        }
        
        // Copy all files from source templates to target templates
        $this->copyDirectoryContents($sourceTemplatesPath, $targetTemplatesPath);
    }
    
    private function copyDirectoryContents($sourceDir, $targetDir)
    {
        $files = scandir($sourceDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourcePath = $sourceDir . '/' . $file;
            $targetPath = $targetDir . '/' . $file;
            
            if (is_dir($sourcePath)) {
                // If it's a directory, create it and recursively copy its contents
                if (!is_dir($targetPath)) {
                    if (!@mkdir($targetPath, 0755, true)) {
                        throw new \RuntimeException("Failed to create directory: {$targetPath}");
                    }
                    $this->info("Created directory: {$targetPath}");
                }
                $this->copyDirectoryContents($sourcePath, $targetPath);
            } else {
                // If it's a file, copy it
                $this->copyTemplateFile($sourcePath, $targetPath, $file);
            }
        }
    }
    
    private function copyTemplateFile($sourcePath, $targetPath, $fileName)
    {
        // Check if file already exists
        if (file_exists($targetPath) && !$this->nonInteractive) {
            echo "Template file already exists: {$targetPath}" . PHP_EOL;
            echo "Do you want to overwrite it? (y/N): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            
            if (strtolower(trim($line)) !== 'y') {
                $this->info("Skipped template: {$fileName}");
                return;
            }
        } elseif (file_exists($targetPath) && $this->nonInteractive) {
            $this->info("Template file already exists (non-interactive mode): {$targetPath} - will be overwritten");
        }
        
        // Copy the file
        if (!copy($sourcePath, $targetPath)) {
            throw new \RuntimeException("Failed to copy template file: {$sourcePath} to {$targetPath}");
        }
        
        $this->info("Copied template: {$fileName}");
    }

    /**
     * Copy user-related files from startup template to appropriate directories
     * 
     * @param string $startupPath The base startup directory path
     */
    private function copyUserFiles(string $startupPath)
    {
        $userDir = $startupPath . '/user';
        if (!is_dir($userDir)) {
            $this->warning("User template directory not found: {$userDir}");
            return;
        }

        // Define target directories and their corresponding files
        $fileMappings = [
            'app/api' => ['User.php'],
            'app/controller' => ['UserController.php'],
            'app/model' => ['UserModel.php'],
            'app/table' => ['UserTable.php']
        ];

        // Create directories if they don't exist
        foreach (array_keys($fileMappings) as $dir) {
            $targetDir = $this->basePath . '/' . $dir;
            if (!is_dir($targetDir)) {
                if (!@mkdir($targetDir, 0755, true)) {
                    $this->warning("Failed to create directory: {$targetDir}");
                    continue;
                }
                $this->info("Created directory: {$targetDir}");
            }
        }

        // Copy each file to its target directory
        foreach ($fileMappings as $targetDir => $files) {
            $fullTargetDir = $this->basePath . '/' . $targetDir;
            
            foreach ($files as $file) {
                $sourceFile = $userDir . '/' . $file;
                $targetFile = $fullTargetDir . '/' . $file;

                if (!file_exists($sourceFile)) {
                    $this->warning("Source file not found: {$sourceFile}");
                    continue;
                }

                // Check if file already exists
                if (file_exists($targetFile) && !$this->nonInteractive) {
                    echo "File already exists: {$targetFile}" . PHP_EOL;
                    echo "Do you want to overwrite it? (y/N): ";
                    $handle = fopen("php://stdin", "r");
                    $line = fgets($handle);
                    fclose($handle);
                    
                    if (strtolower(trim($line)) !== 'y') {
                        $this->info("Skipped: {$file}");
                        continue;
                    }
                } elseif (file_exists($targetFile) && $this->nonInteractive) {
                    $this->info("File already exists (non-interactive mode): {$targetFile} - will be overwritten");
                }

                // Copy the file
                if (!copy($sourceFile, $targetFile)) {
                    $this->warning("Failed to copy file: {$sourceFile} to {$targetFile}");
                    continue;
                }

                        $this->info("Copied: {$file} to {$targetDir}");
            }
        }
    }

    /**
     * Offer PHPStan installation to the user
     * 
     * @return void
     */
    private function offerPhpstanInstallation(): void
    {
        // Skip if in non-interactive mode
        if ($this->nonInteractive) {
            $this->info("Skipped PHPStan installation (non-interactive mode)");
            return;
        }

        $this->write("\n\033[1;33m╭─ PHPStan Installation ───────────────────────────────────────╮\033[0m\n", 'yellow');
        $this->write("\033[1;33m│\033[0m \033[1;94mWould you like to install PHPStan for static analysis?\033[0m        \033[1;33m│\033[0m\n", 'white');
        $this->write("\033[1;33m│\033[0m \033[1;36mPHPStan will help catch bugs and improve code quality\033[0m      \033[1;33m│\033[0m\n", 'white');
        $this->write("\033[1;33m│\033[0m \033[1;36mThis will install phpstan/phpstan as a dev dependency\033[0m    \033[1;33m│\033[0m\n", 'white');
        $this->write("\033[1;33m╰───────────────────────────────────────────────────────────────╯\033[0m\n", 'yellow');
        
        echo "\n\033[1;36mInstall PHPStan? (y/N):\033[0m ";
        $handle = fopen("php://stdin", "r");
        $choice = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($choice) === 'y') {
            $this->installPhpstan();
        } else {
            $this->info("PHPStan installation skipped");
        }
    }

    /**
     * Install PHPStan and copy configuration file
     * 
     * @return void
     */
    private function installPhpstan(): void
    {
        try {
            $this->info("Installing PHPStan...");
            
            // Check if composer.json exists
            $composerJsonPath = $this->basePath . '/composer.json';
            if (!file_exists($composerJsonPath)) {
                $this->warning("composer.json not found. Please run 'composer init' first.");
                return;
            }
            
            // Install PHPStan via Composer
            $this->info("Running: composer require --dev phpstan/phpstan");
            $output = [];
            $returnCode = 0;
            
            // Change to project directory
            $currentDir = getcwd();
            chdir($this->basePath);
            
            // Execute composer require command
            exec('composer require --dev phpstan/phpstan 2>&1', $output, $returnCode);
            
            // Restore original directory
            chdir($currentDir);
            
            if ($returnCode !== 0) {
                $this->warning("Failed to install PHPStan via Composer. Error output:");
                foreach ($output as $line) {
                    $this->write("  {$line}\n", 'red');
                }
                $this->warning("You can manually install PHPStan with: composer require --dev phpstan/phpstan");
                return;
            }
            
            $this->info("PHPStan installed successfully!");
            
            // Copy phpstan.neon configuration file
            $this->copyPhpstanConfig();
            
            // Add composer script for PHPStan
            $this->addPhpstanComposerScript();
            
        } catch (\Exception $e) {
            $this->warning("PHPStan installation failed: " . $e->getMessage());
            $this->warning("You can manually install PHPStan with: composer require --dev phpstan/phpstan");
        }
    }

    /**
     * Copy PHPStan configuration file to project root
     * 
     * @return void
     */
    private function copyPhpstanConfig(): void
    {
        try {
            $sourceConfig = $this->packagePath . '/src/startup/phpstan.neon';
            $targetConfig = $this->basePath . '/phpstan.neon';
            
            if (!file_exists($sourceConfig)) {
                $this->warning("PHPStan configuration template not found: {$sourceConfig}");
                return;
            }
            
            // Check if target already exists
            if (file_exists($targetConfig)) {
                echo "PHPStan configuration already exists: {$targetConfig}" . PHP_EOL;
                echo "Do you want to overwrite it? (y/N): ";
                $handle = fopen("php://stdin", "r");
                $line = fgets($handle);
                fclose($handle);
                
                if (strtolower(trim($line)) !== 'y') {
                    $this->info("Skipped PHPStan configuration copy");
                    return;
                }
            }
            
            if (!copy($sourceConfig, $targetConfig)) {
                throw new \RuntimeException("Failed to copy PHPStan configuration file");
            }
            
            $this->info("PHPStan configuration copied: {$targetConfig}");
            
        } catch (\Exception $e) {
            $this->warning("Failed to copy PHPStan configuration: " . $e->getMessage());
        }
    }

    /**
     * Add PHPStan script to composer.json
     * 
     * @return void
     */
    private function addPhpstanComposerScript(): void
    {
        try {
            $composerJsonPath = $this->basePath . '/composer.json';
            $composerJson = json_decode(file_get_contents($composerJsonPath), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->warning("Failed to parse composer.json");
                return;
            }
            
            // Initialize scripts section if it doesn't exist
            if (!isset($composerJson['scripts'])) {
                $composerJson['scripts'] = [];
            }
            
            // Add PHPStan scripts if they don't exist
            if (!isset($composerJson['scripts']['phpstan'])) {
                $composerJson['scripts']['phpstan'] = 'phpstan analyse';
                $this->info("Added PHPStan script to composer.json");
            }
            
            if (!isset($composerJson['scripts']['phpstan:check'])) {
                $composerJson['scripts']['phpstan:check'] = 'phpstan analyse --error-format=json';
                $this->info("Added PHPStan check script to composer.json");
            }
            
            // Write updated composer.json
            $updatedJson = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!file_put_contents($composerJsonPath, $updatedJson)) {
                throw new \RuntimeException("Failed to update composer.json");
            }
            
                         $this->info("Composer.json updated with PHPStan scripts");
             
         } catch (\Exception $e) {
             $this->warning("Failed to update composer.json: " . $e->getMessage());
         }
     }

     /**
      * Offer testing framework installation to the user
      * 
      * @return void
      */
     private function offerTestingFrameworkInstallation(): void
     {
         // Skip if in non-interactive mode
         if ($this->nonInteractive) {
             $this->info("Skipped testing framework installation (non-interactive mode)");
             return;
         }

         $this->write("\n\033[1;33m╭─ Testing Framework Installation ───────────────────────────────╮\033[0m\n", 'yellow');
         $this->write("\033[1;33m│\033[0m \033[1;94mWould you like to install a testing framework?\033[0m                \033[1;33m│\033[0m\n", 'white');
         $this->write("\033[1;33m│\033[0m \033[1;36mChoose between PHPUnit (traditional) or Pest (modern & expressive)\033[0m \033[1;33m│\033[0m\n", 'white');
         $this->write("\033[1;33m╰──────────────────────────────────────────────────────────────────╯\033[0m\n", 'yellow');
         
         echo "\n\033[1;36mChoose testing framework:\033[0m\n";
         echo "  [\033[32m1\033[0m] \033[1mPHPUnit\033[0m - Traditional PHP testing framework\n";
         echo "  [\033[32m2\033[0m] \033[1mPest\033[0m - Modern, expressive testing framework\n";
         echo "  [\033[32m3\033[0m] \033[1mSkip\033[0m - No testing framework\n";
         echo "\n\033[1;36mEnter choice (1-3):\033[0m ";
         
         $handle = fopen("php://stdin", "r");
         $choice = trim(fgets($handle));
         fclose($handle);
         
         switch ($choice) {
             case '1':
                 $this->installPhpunit();
                 break;
             case '2':
                 $this->installPest();
                 break;
             case '3':
                 $this->info("Testing framework installation skipped");
                 break;
             default:
                 $this->warning("Invalid choice. Testing framework installation skipped.");
                 break;
         }
     }

     /**
      * Install PHPUnit testing framework
      * 
      * @return void
      */
     private function installPhpunit(): void
     {
         try {
             $this->info("Installing PHPUnit...");
             
             // Check if composer.json exists
             $composerJsonPath = $this->basePath . '/composer.json';
             if (!file_exists($composerJsonPath)) {
                 $this->warning("composer.json not found. Please run 'composer init' first.");
                 return;
             }
             
             // Install PHPUnit via Composer
             $this->info("Running: composer require --dev phpunit/phpunit");
             $output = [];
             $returnCode = 0;
             
             // Change to project directory
             $currentDir = getcwd();
             chdir($this->basePath);
             
             // Execute composer require command
             exec('composer require --dev phpunit/phpunit 2>&1', $output, $returnCode);
             
             // Restore original directory
             chdir($currentDir);
             
             if ($returnCode !== 0) {
                 $this->warning("Failed to install PHPUnit via Composer. Error output:");
                 foreach ($output as $line) {
                     $this->write("  {$line}\n", 'red');
                 }
                 $this->warning("You can manually install PHPUnit with: composer require --dev phpunit/phpunit");
                 return;
             }
             
             $this->info("PHPUnit installed successfully!");
             
             // Create PHPUnit configuration file
             $this->createPhpunitConfig();
             
             // Add composer scripts for PHPUnit
             $this->addPhpunitComposerScripts();
             
         } catch (\Exception $e) {
             $this->warning("PHPUnit installation failed: " . $e->getMessage());
             $this->warning("You can manually install PHPUnit with: composer require --dev phpunit/phpunit");
         }
     }

     /**
      * Install Pest testing framework
      * 
      * @return void
      */
     private function installPest(): void
     {
         try {
             $this->info("Installing Pest...");
             
             // Check if composer.json exists
             $composerJsonPath = $this->basePath . '/composer.json';
             if (!file_exists($composerJsonPath)) {
                 $this->warning("composer.json not found. Please run 'composer init' first.");
                 return;
             }
             
             // Install Pest via Composer
             $this->info("Running: composer require --dev pestphp/pest");
             $output = [];
             $returnCode = 0;
             
             // Change to project directory
             $currentDir = getcwd();
             chdir($this->basePath);
             
             // Execute composer require command
             exec('composer require --dev pestphp/pest 2>&1', $output, $returnCode);
             
             // Restore original directory
             chdir($currentDir);
             
             if ($returnCode !== 0) {
                 $this->warning("Failed to install Pest via Composer. Error output:");
                 foreach ($output as $line) {
                     $this->write("  {$line}\n", 'red');
                 }
                 $this->warning("You can manually install Pest with: composer require --dev pestphp/pest");
                 return;
             }
             
             $this->info("Pest installed successfully!");
             
             // Initialize Pest
             $this->initializePest();
             
             // Add composer scripts for Pest
             $this->addPestComposerScripts();
             
         } catch (\Exception $e) {
             $this->warning("Pest installation failed: " . $e->getMessage());
             $this->warning("You can manually install Pest with: composer require --dev pestphp/pest");
         }
     }

     /**
      * Create PHPUnit configuration file
      * 
      * @return void
      */
     private function createPhpunitConfig(): void
     {
         try {
             $targetConfig = $this->basePath . '/phpunit.xml';
             
             // Check if target already exists
             if (file_exists($targetConfig)) {
                 echo "PHPUnit configuration already exists: {$targetConfig}" . PHP_EOL;
                 echo "Do you want to overwrite it? (y/N): ";
                 $handle = fopen("php://stdin", "r");
                 $line = fgets($handle);
                 fclose($handle);
                 
                 if (strtolower(trim($line)) !== 'y') {
                     $this->info("Skipped PHPUnit configuration creation");
                     return;
                 }
             }
             
             // Create basic PHPUnit configuration
             $phpunitConfig = '<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="GEMVC Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">app</directory>
        </include>
    </coverage>
</phpunit>';
             
             if (!file_put_contents($targetConfig, $phpunitConfig)) {
                 throw new \RuntimeException("Failed to create PHPUnit configuration file");
             }
             
             $this->info("PHPUnit configuration created: {$targetConfig}");
             
             // Create tests directory
             $testsDir = $this->basePath . '/tests';
             if (!is_dir($testsDir)) {
                 if (!@mkdir($testsDir, 0755, true)) {
                     $this->warning("Failed to create tests directory: {$testsDir}");
                 } else {
                     $this->info("Created tests directory: {$testsDir}");
                 }
             }
             
         } catch (\Exception $e) {
             $this->warning("Failed to create PHPUnit configuration: " . $e->getMessage());
         }
     }

     /**
      * Initialize Pest testing framework
      * 
      * @return void
      */
     private function initializePest(): void
     {
         try {
             $this->info("Initializing Pest...");
             
             // Change to project directory
             $currentDir = getcwd();
             chdir($this->basePath);
             
             // Run Pest init command
             $output = [];
             $returnCode = 0;
             exec('./vendor/bin/pest --init 2>&1', $output, $returnCode);
             
             // Restore original directory
             chdir($currentDir);
             
             if ($returnCode !== 0) {
                 $this->warning("Failed to initialize Pest. Error output:");
                 foreach ($output as $line) {
                     $this->write("  {$line}\n", 'red');
                 }
                 $this->warning("You can manually initialize Pest with: ./vendor/bin/pest --init");
                 return;
             }
             
             $this->info("Pest initialized successfully!");
             
         } catch (\Exception $e) {
             $this->warning("Failed to initialize Pest: " . $e->getMessage());
         }
     }

     /**
      * Add PHPUnit composer scripts
      * 
      * @return void
      */
     private function addPhpunitComposerScripts(): void
     {
         try {
             $composerJsonPath = $this->basePath . '/composer.json';
             $composerJson = json_decode(file_get_contents($composerJsonPath), true);
             
             if (json_last_error() !== JSON_ERROR_NONE) {
                 $this->warning("Failed to parse composer.json");
                 return;
             }
             
             // Initialize scripts section if it doesn't exist
             if (!isset($composerJson['scripts'])) {
                 $composerJson['scripts'] = [];
             }
             
             // Add PHPUnit scripts if they don't exist
             if (!isset($composerJson['scripts']['test'])) {
                 $composerJson['scripts']['test'] = 'phpunit';
                 $this->info("Added PHPUnit test script to composer.json");
             }
             
             if (!isset($composerJson['scripts']['test:coverage'])) {
                 $composerJson['scripts']['test:coverage'] = 'phpunit --coverage-html coverage';
                 $this->info("Added PHPUnit coverage script to composer.json");
             }
             
             // Write updated composer.json
             $updatedJson = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
             if (!file_put_contents($composerJsonPath, $updatedJson)) {
                 throw new \RuntimeException("Failed to update composer.json");
             }
             
             $this->info("Composer.json updated with PHPUnit scripts");
             
         } catch (\Exception $e) {
             $this->warning("Failed to update composer.json: " . $e->getMessage());
         }
     }

     /**
      * Add Pest composer scripts
      * 
      * @return void
      */
     private function addPestComposerScripts(): void
     {
         try {
             $composerJsonPath = $this->basePath . '/composer.json';
             $composerJson = json_decode(file_get_contents($composerJsonPath), true);
             
             if (json_last_error() !== JSON_ERROR_NONE) {
                 $this->warning("Failed to parse composer.json");
                 $this->warning("Failed to parse composer.json");
                 return;
             }
             
             // Initialize scripts section if it doesn't exist
             if (!isset($composerJson['scripts'])) {
                 $composerJson['scripts'] = [];
             }
             
             // Add Pest scripts if they don't exist
             if (!isset($composerJson['scripts']['test'])) {
                 $composerJson['scripts']['test'] = 'pest';
                 $this->info("Added Pest test script to composer.json");
             }
             
             if (!isset($composerJson['scripts']['test:parallel'])) {
                 $composerJson['scripts']['test:parallel'] = 'pest --parallel';
                 $this->info("Added Pest parallel test script to composer.json");
             }
             
             // Write updated composer.json
             $updatedJson = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
             if (!file_put_contents($composerJsonPath, $updatedJson)) {
                 throw new \RuntimeException("Failed to update composer.json");
             }
             
             $this->info("Composer.json updated with Pest scripts");
             
         } catch (\Exception $e) {
             $this->warning("Failed to update composer.json: " . $e->getMessage());
         }
     }
 } 