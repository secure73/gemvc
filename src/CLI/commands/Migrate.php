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
            $tableName = $this->args[0] ?? null;
            
            if ($tableName) {
                $this->migrateSingleTable($tableName);
            } else {
                $this->error("Please specify a table class name: gemvc db:migrate TableClassName");
            }
        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
        }
    }
    
    private function migrateSingleTable(string $tableName)
    {
        $this->info("Migrating table: {$tableName}");
        
        // Convert table name to class name if needed
        $className = $this->formatTableClassName($tableName);
        
        // Check if table class exists
        $tableClass = "App\\Table\\{$className}";
        if (!class_exists($tableClass)) {
            throw new \Exception("Table class not found: {$tableClass}");
        }
        
        // Create table instance
        $table = new $tableClass();
        
        // Get table generator
        $generator = new TableGenerator();
        
        // Create or update table
        if ($generator->tableExists($table->getTable())) {
            $this->info("Updating existing table...");
            $generator->updateTable($table);
            $this->success("Table updated successfully!");
        } else {
            $this->info("Creating new table...");
            $generator->createTableFromObject($table);
            $this->success("Table created successfully!");
        }
    }
    
    private function formatTableClassName(string $name): string
    {
        // Remove 'Table' suffix if present
        $name = preg_replace('/Table$/', '', $name);
        
        // Add 'Table' suffix if not present
        if (!str_ends_with($name, 'Table')) {
            $name .= 'Table';
        }
        
        return $name;
    }
} 