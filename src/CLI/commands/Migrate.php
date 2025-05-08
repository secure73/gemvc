<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\Database\Migration;

class Migrate extends Command
{
    private $basePath;
    private $migrationsFile;

    public function execute()
    {
        $this->basePath = dirname(dirname(dirname(__DIR__)));
        $migrationsPath = $this->basePath . '/app/database/migrations';
        $this->migrationsFile = $this->basePath . '/app/database/migrations.json';

        if (!is_dir($migrationsPath)) {
            $this->error("Migrations directory not found: {$migrationsPath}");
        }

        // Create migrations.json if it doesn't exist
        if (!file_exists($this->migrationsFile)) {
            file_put_contents($this->migrationsFile, json_encode(['migrations' => []]));
        }

        $files = glob($migrationsPath . '/*.php');
        if (empty($files)) {
            $this->warning("No migration files found in {$migrationsPath}");
            return;
        }

        $migrations = json_decode(file_get_contents($this->migrationsFile), true);
        $migrations = $migrations['migrations'] ?? [];

        foreach ($files as $file) {
            // Get class name from filename
            $filename = basename($file, '.php');
            
            // Skip if migration is already run
            if (in_array($filename, $migrations)) {
                $this->info("Migration already run: {$filename}");
                continue;
            }

            $this->info("Running migration: {$filename}");
            
            // Include the migration file
            require_once $file;
            
            $class = "App\\Database\\Migrations\\{$filename}";
            if (!class_exists($class)) {
                $this->error("Migration class not found: {$class}");
            }

            $migration = new $class();
            if (!$migration instanceof Migration) {
                $this->error("Invalid migration class: {$class}");
            }

            try {
                $migration->up();
                // Record migration as run
                $migrations[] = $filename;
                file_put_contents($this->migrationsFile, json_encode(['migrations' => $migrations], JSON_PRETTY_PRINT));
                $this->success("Migration completed: {$filename}");
            } catch (\Exception $e) {
                $this->error("Migration failed: {$filename} - " . $e->getMessage());
            }
        }

        $this->success("All migrations completed successfully!");
    }
} 