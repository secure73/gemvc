<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Commands\BaseCrudGenerator;

class CreateService extends BaseCrudGenerator
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
     * Parse command line flags
     * 
     * @return void
     */
    protected function parseFlags(): void
    {
        $this->flags = [
            'controller' => false,
            'model' => false,
            'table' => false
        ];

        // Check for combined flags (e.g., -cmt)
        if (isset($this->args[1]) && strpos($this->args[1], '-') === 0) {
            $flagStr = substr($this->args[1], 1);
            $this->flags['controller'] = strpos($flagStr, 'c') !== false;
            $this->flags['model'] = strpos($flagStr, 'm') !== false;
            $this->flags['table'] = strpos($flagStr, 't') !== false;
        }
    }

    public function execute(): void
    {
        if (empty($this->args[0])) {
            $this->error("Service name is required. Usage: gemvc create:service ServiceName [-c|-m|-t]");
        }

        $this->serviceName = $this->formatServiceName($this->args[0]);
        $this->basePath = defined('PROJECT_ROOT') ? PROJECT_ROOT : $this->determineProjectRoot();
        $this->parseFlags();

        try {
            // Create necessary directories
            $this->createDirectories($this->getRequiredDirectories());

            // Create service file
            $this->createService();

            // Create additional files based on flags
            if ($this->flags['controller']) {
                $this->createController();
            }
            if ($this->flags['model']) {
                $this->createModel();
            }
            if ($this->flags['table']) {
                $this->createTable();
            }

            $this->success("Service {$this->serviceName} created successfully!");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

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

    protected function createService(): void
    {
        $template = $this->getTemplate('service');
        $content = $this->replaceTemplateVariables($template, [
            'serviceName' => $this->serviceName
        ]);

        $path = $this->basePath . "/app/api/{$this->serviceName}.php";
        $this->writeFile($path, $content, "Service");
    }

    protected function createController(): void
    {
        $template = $this->getTemplate('controller');
        $content = $this->replaceTemplateVariables($template, [
            'serviceName' => $this->serviceName
        ]);

        $path = $this->basePath . "/app/controller/{$this->serviceName}Controller.php";
        $this->writeFile($path, $content, "Controller");
    }

    protected function createModel(): void
    {
        $template = $this->getTemplate('model');
        $content = $this->replaceTemplateVariables($template, [
            'serviceName' => $this->serviceName
        ]);

        $path = $this->basePath . "/app/model/{$this->serviceName}Model.php";
        $this->writeFile($path, $content, "Model");
    }

    protected function createTable(): void
    {
        $template = $this->getTemplate('table');
        $content = $this->replaceTemplateVariables($template, [
            'serviceName' => $this->serviceName,
            'tableName' => strtolower($this->serviceName) . 's'
        ]);

        $path = $this->basePath . "/app/table/{$this->serviceName}Table.php";
        $this->writeFile($path, $content, "Table");
    }

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
} 