<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\Database\TableGenerator;
use Gemvc\Helper\ProjectHelper;

class DbMigrate extends Command
{
    protected string $description = "Create or update database tables based on their PHP class definitions. This command will:
    - Create new tables if they don't exist
    - Update existing tables to match their class definitions
    - Add new columns for new properties
    - Update column types if changed
    - Remove columns not in the class (with --force flag)
    - Update nullable status
    - Manage indexes";

    public function execute()
    {
        try {
            ProjectHelper::loadEnv();

            if (empty($this->args[0])) {
                $this->error("Table class name is required. Usage: gemvc db:migrate TableClassName [--force]");
                return;
            }

            $tableClass = $this->args[0];
            $force = in_array('--force', $this->args);
            $enforceNotNull = in_array('--enforce-not-null', $this->args);

            $defaultValue = null;
            foreach ($this->args as $i => $arg) {
                if ($arg === '--default' && isset($this->args[$i + 1])) {
                    $defaultValue = $this->args[$i + 1];
                }
            }

            $tableFile = ProjectHelper::rootDir() . '/app/table/' . $tableClass . '.php';

            if (!file_exists($tableFile)) {
                $this->error("Table file not found: {$tableFile}");
                return;
            }

            require_once $tableFile;
            $className = "App\\Table\\{$tableClass}";
            if (!class_exists($className)) {
                $this->error("Table class not found: {$className}");
                return;
            }

            $table = new $className();
            $generator = new TableGenerator();

            // Ensure we have a fresh connection
            if (!$generator->reconnect()) {
                $this->error("Failed to establish database connection");
                return;
            }

            // First check if table exists
            $tableName = $table->getTable();
            $connection = $generator->getConnection();
            $stmt = $connection->query("SHOW TABLES LIKE '{$tableName}'");
            $tableExists = $stmt->rowCount() > 0;

            if ($tableExists) {
                $this->info("Table '{$tableName}' exists. Syncing with class definition...");
                if ($force) {
                    $this->info("Force flag detected. Will remove columns not in class definition.");
                }
                if ($generator->updateTable($table, null, $force, $enforceNotNull, $defaultValue)) {
                    $this->success("Table '{$tableName}' synchronized successfully!");
                } else {
                    $this->error("Failed to sync table: " . $generator->getError());
                }
            } else {
                $this->info("Table '{$tableName}' does not exist. Creating new table...");
                if ($generator->createTableFromObject($table)) {
                    $this->success("Table '{$tableName}' created successfully!");
                } else {
                    $this->error("Failed to create table: " . $generator->getError());
                }
            }
        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
        }
    }
} 