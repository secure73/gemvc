<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\Database\TableGenerator;
use Symfony\Component\Dotenv\Dotenv;

class Migrate extends Command
{
    private $basePath;
    private $migrationsFile;

    public function execute()
    {
        // Find project root by going up from vendor directory
        $this->basePath = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))); // Go up to project root
        
        echo "Debug - Project root path: " . $this->basePath . "\n";
        echo "Debug - Loading .env file from: " . $this->basePath . '/app/.env' . "\n";
        
        // Load environment variables from /app/.env
        $dotenv = new Dotenv();
        $dotenv->load($this->basePath . '/app/.env');
        
        // Debug: Print environment variables after loading
        echo "\nDebug - Environment Variables After Loading:\n";
        echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
        echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'not set') . "\n";
        echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'not set') . "\n";
        echo "DB_CHARSET: " . ($_ENV['DB_CHARSET'] ?? 'not set') . "\n";
        echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'not set') . "\n";
        echo "DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? 'set' : 'not set') . "\n";
        
        $tablesPath = $this->basePath . '/app/table';
        $this->migrationsFile = $this->basePath . '/app/database/migrations.json';

        if (!is_dir($tablesPath)) {
            $this->error("Table directory not found: {$tablesPath}");
            return;
        }

        // Create migrations.json if it doesn't exist
        if (!file_exists($this->migrationsFile)) {
            file_put_contents($this->migrationsFile, json_encode(['tables' => []]));
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
    }

    private function migrateTable(string $tableName)
    {
        $tablesPath = $this->basePath . '/app/table';
        $file = $tablesPath . '/' . $tableName . '.php';

        if (!file_exists($file)) {
            $this->error("Table file not found: {$file}");
            return;
        }

        $migrations = json_decode(file_get_contents($this->migrationsFile), true);
        $migrations = $migrations['tables'] ?? [];

        // Skip if table is already migrated
        if (in_array($tableName, $migrations)) {
            $this->info("Table already migrated: {$tableName}");
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
                // Record table as migrated
                $migrations[] = $tableName;
                file_put_contents($this->migrationsFile, json_encode(['tables' => $migrations], JSON_PRETTY_PRINT));
                $this->success("Table migrated successfully: {$tableName}");
            } else {
                $this->error("Failed to migrate table: {$tableName} - " . $tableGenerator->getError());
            }
        } catch (\Exception $e) {
            $this->error("Migration failed: {$tableName} - " . $e->getMessage());
        }
    }
} 