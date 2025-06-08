<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;

abstract class BaseGenerator extends Command
{
    protected $serviceName;
    protected $basePath;
    protected $flags = [];

    /**
     * Format service name to proper case
     * 
     * @param string $name
     * @return string
     */
    protected function formatServiceName(string $name): string
    {
        return ucfirst(strtolower($name));
    }

    /**
     * Create necessary directories
     * 
     * @param array $directories
     * @return void
     * @throws \RuntimeException
     */
    protected function createDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                if (!@mkdir($directory, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$directory}");
                }
                $this->info("Created directory: {$directory}");
            }
        }
    }

    /**
     * Confirm file overwrite
     * 
     * @param string $path
     * @return bool
     */
    protected function confirmOverwrite(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }
        
        echo "File already exists: {$path}" . PHP_EOL;
        echo "Do you want to overwrite it? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        return strtolower(trim($line)) === 'y';
    }

    /**
     * Write file to disk
     * 
     * @param string $path
     * @param string $content
     * @param string $fileType
     * @return void
     */
    protected function writeFile(string $path, string $content, string $fileType): void
    {
        if (!$this->confirmOverwrite($path)) {
            $this->info("Skipped {$fileType}: " . basename($path));
            return;
        }

        if (!file_put_contents($path, $content)) {
            $this->error("Failed to create {$fileType} file: {$path}");
        }
        $this->info("Created {$fileType}: " . basename($path));
    }

    /**
     * Determine project root directory
     * 
     * @return string
     */
    protected function determineProjectRoot(): string
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

    /**
     * Get template content from project root
     * 
     * @param string $templateName
     * @return string
     */
    protected function getTemplate(string $templateName): string
    {
        // Try project root templates first (user customizable)
        $projectRoot = $this->basePath ?? $this->determineProjectRoot();
        $templatePath = $projectRoot .DIRECTORY_SEPARATOR. "templates".DIRECTORY_SEPARATOR."cli".DIRECTORY_SEPARATOR. "{$templateName}.template";
        
        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }
        
        // Fallback to vendor templates if project templates don't exist
        $vendorTemplatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "cli" . DIRECTORY_SEPARATOR . "{$templateName}.template";
        if (file_exists($vendorTemplatePath)) {
            $this->warning("Using vendor template for {$templateName} - consider copying templates to project root");
            return file_get_contents($vendorTemplatePath);
        }
        
        throw new \RuntimeException("Template not found: {$templateName} (checked: {$templatePath}, {$vendorTemplatePath})");
    }

    /**
     * Replace template variables
     * 
     * @param string $content
     * @param array $variables
     * @return string
     */
    protected function replaceTemplateVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{$' . $key . '}', $value, $content);
        }
        return $content;
    }

    /**
     * Execute the command
     * 
     * @return void
     */
    abstract public function execute(): void;
} 