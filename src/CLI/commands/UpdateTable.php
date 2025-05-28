<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\Database\TableGenerator;
use Gemvc\Helper\ProjectHelper;

class UpdateTable extends Command
{
    private $basePath;
    private $tableGenerator;

    public function execute()
    {
        try {
            // 1. Setup
            $this->basePath = ProjectHelper::rootDir();
            ProjectHelper::loadEnv();
            $this->tableGenerator = new TableGenerator();
            
            // 2. Validate arguments
            if (empty($this->args[0])) {
                $this->error("Table name is required. Usage: gemvc update:table TableName");
                return;
            }

            $tableName = $this->args[0];
            
            // 3. Validate table file exists
            $tableFile = $this->basePath . '/app/table/' . $tableName . '.php';
            if (!file_exists($tableFile)) {
                $this->error("Table file not found: {$tableFile}");
                return;
            }

            // 4. Load table class
            require_once $tableFile;
            $className = "App\\Table\\{$tableName}";
            if (!class_exists($className)) {
                $this->error("Table class not found: {$className}");
                return;
            }

            // 5. Create table instance
            $table = new $className();
            
            // 6. Update table
            $this->info("Updating table: {$tableName}");
            
            if ($this->tableGenerator->updateTable($table, null, true)) {
                $this->success("Table updated successfully: {$tableName}");
            } else {
                $this->error("Failed to update table: {$tableName} - " . $this->tableGenerator->getError());
            }
        } catch (\Exception $e) {
            $this->error("Update failed: " . $e->getMessage());
        }
    }
} 