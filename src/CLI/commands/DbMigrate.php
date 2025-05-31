<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\Database\TableGenerator;
use Gemvc\Helper\ProjectHelper;

class DbMigrate extends Command
{
    public function execute()
    {
        try {
            ProjectHelper::loadEnv();

            if (empty($this->args[0])) {
                $this->error("Table class name is required. Usage: gemvc db:migrate TableClassName");
                return;
            }

            $tableClass = $this->args[0];
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

            // First check if table exists
            $tableName = $table->getTable();
            $connection = $generator->getConnection();
            $stmt = $connection->query("SHOW TABLES LIKE '{$tableName}'");
            $tableExists = $stmt->rowCount() > 0;

            if ($tableExists) {
                $this->info("Table '{$tableName}' exists. Syncing with class definition...");
                if ($generator->updateTable($table)) {
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