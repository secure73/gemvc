<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\CLI\Commands\DbConnect;
use Gemvc\Helper\ProjectHelper;

class DbList extends Command
{
    public function execute()
    {
        try {
            $this->info("Fetching database tables...");
            
            // Load environment variables
            ProjectHelper::loadEnv();
            
            // Get database name from environment
            $dbName = $_ENV['DB_NAME'] ?? null;
            if (!$dbName) {
                throw new \Exception("Database name not found in configuration (DB_NAME)");
            }
            
            // Get database connection
            
            $pdo = DbConnect::connect();
            if (!$pdo) {
                return;
            }
            
            // Get all tables
            $stmt = $pdo->query("SHOW TABLES FROM `{$dbName}`");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                $this->info("No tables found in database '{$dbName}'");
                return;
            }
            
            // Display tables and their columns
            $this->write("\nTables in database '{$dbName}':\n", 'yellow');
            foreach ($tables as $table) {
                $this->write("\nTable: {$table}\n", 'green');
                
                // Get columns for this table
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
                $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                if (empty($columns)) {
                    $this->write("  No columns found\n", 'red');
                    continue;
                }
                
                // Display column information
                $this->write("  Columns:\n", 'cyan');
                foreach ($columns as $column) {
                    $type = $column['Type'];
                    $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                    $key = $column['Key'] ? "({$column['Key']})" : '';
                    $default = $column['Default'] !== null ? "DEFAULT {$column['Default']}" : '';
                    $extra = $column['Extra'] ? " {$column['Extra']}" : '';
                    
                    $columnInfo = sprintf(
                        "    - %s: %s %s %s %s %s",
                        $column['Field'],
                        $type,
                        $null,
                        $key,
                        $default,
                        $extra
                    );
                    $this->write(trim($columnInfo) . "\n", 'white');
                }
            }
            $this->write("\n");
            
        } catch (\Exception $e) {
            $this->error("Failed to list tables: " . $e->getMessage());
        }
    }
} 