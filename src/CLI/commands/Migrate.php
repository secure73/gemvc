<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\Database\TableGenerator;
use Gemvc\Helper\ProjectHelper;

class Migrate extends Command
{
    private $basePath;

    public function execute()
    {
        try {
            // Use ProjectHelper to get root directory and load env
            $this->basePath = ProjectHelper::rootDir();
            ProjectHelper::loadEnv();
            $tablesPath = $this->basePath . '/app/table';

            if (!is_dir($tablesPath)) {
                $this->error("Table directory not found: {$tablesPath}");
                return;
            }

            // Get specific table to migrate if provided
            $specificTable = $this->args[0] ?? null;
            
            if ($specificTable) {
                // Migrate specific table
                $this->migrateTable($specificTable);
            } else {
                // Migrate all tables
                $files = glob($tablesPath . '/*.php');
                if (empty($files)) {
                    $this->warning("No table files found in {$tablesPath}");
                    return;
                }

                foreach ($files as $file) {
                    $filename = basename($file, '.php');
                    $this->migrateTable($filename);
                }
            }

            $this->success("Migration completed successfully!");
        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
        }
    }

    private function migrateTable(string $tableName)
    {
        $tablesPath = $this->basePath . '/app/table';
        $file = $tablesPath . '/' . $tableName . '.php';

        if (!file_exists($file)) {
            $this->error("Table file not found: {$file}");
            return;
        }

        $this->info("Migrating table: {$tableName}");
        
        // Include the table file
        require_once $file;
        
        $class = "App\\Table\\{$tableName}";
        if (!class_exists($class)) {
            $this->error("Table class not found: {$class}");
            return;
        }

        try {
            $table = new $class();
            $tableGenerator = new TableGenerator();
            
            // Create or update table using TableGenerator
            if ($tableGenerator->createTableFromObject($table)) {
                $this->success("Table migrated successfully: {$tableName}");
            } else {
                $this->error("Failed to migrate table: {$tableName} - " . $tableGenerator->getError());
            }
        } catch (\Exception $e) {
            $this->error("Migration failed: {$tableName} - " . $e->getMessage());
        }
    }
} 