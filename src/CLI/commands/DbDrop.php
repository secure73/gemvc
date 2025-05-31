<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\Database\DBConnection;
use Gemvc\Helper\ProjectHelper;
use PDO;

class DbDrop extends Command
{
    public function execute()
    {
        try {
            // Check if table name is provided
            if (empty($this->args)) {
                throw new \Exception("Table name is required. Usage: db:drop TableName [--force]");
            }

            $tableName = strtolower($this->args[0]);
            $force = in_array('--force', $this->args);

            // Ask for confirmation unless --force is used
            if (!$force) {
                $this->error("\nWARNING: This will permanently delete the table '{$tableName}' and all its data!");
                $this->error("This action cannot be undone.");

                $this->write("\nAre you sure you want to drop this table? (yes/no): ", 'yellow');
                $handle = fopen("php://stdin", "r");
                $confirm = trim(fgets($handle)); // Replaced stream_get_line with fgets
                fclose($handle);

                if (strtolower($confirm) !== 'yes') {
                    $this->info("Operation cancelled.");
                    return;
                }
            }

            // Load environment variables
            ProjectHelper::loadEnv();

            // Get database connection
            $db = new DBConnection();
            $pdo = $db->connect();
            if (!$pdo) {
                throw new \Exception("Failed to connect to database: " . $db->getError());
            }

            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
            if ($stmt->rowCount() === 0) {
                throw new \Exception("Table '{$tableName}' does not exist.");
            }

            // Show table structure before dropping
            $this->info("\nTable structure to be dropped:");
            $stmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
            $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->info($tableInfo['Create Table']);

            // Drop the table
            $pdo->exec("DROP TABLE `{$tableName}`");

            $this->success("Table '{$tableName}' has been dropped successfully!");

        } catch (\Exception $e) {
            $this->error("Failed to drop table: " . $e->getMessage());
        }
    }
}
