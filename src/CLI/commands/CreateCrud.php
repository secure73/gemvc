<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Commands\BaseCrudGenerator;

class CreateCrud extends BaseCrudGenerator
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

    public function execute(): void
    {
        if (empty($this->args[0])) {
            $this->error("Service name is required. Usage: gemvc create:crud ServiceName");
        }

        $this->serviceName = $this->formatServiceName($this->args[0]);
        $this->basePath = defined('PROJECT_ROOT') ? PROJECT_ROOT : $this->determineProjectRoot();

        try {
            // Create service with all components
            $service = new CreateService();
            $service->args = [$this->serviceName, '-cmt']; // Enable all components
            $service->execute();

            $this->success("CRUD Service {$this->serviceName} created successfully!");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
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