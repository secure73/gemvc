<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\CLI\Commands\DbConnect;
use Gemvc\Helper\ProjectHelper;

/**
 * CLI Command to add a unique constraint to a table column.
 *
 * Usage:
 *   vendor/bin/gemvc db:unique table/column
 * Example:
 *   vendor/bin/gemvc db:unique users/email
 *
 * This command will:
 *   - Check for duplicate values in the specified column
 *   - If no duplicates, add a unique constraint to the column
 *   - If duplicates exist, abort and list the duplicates
 */
class DbUnique extends Command
{
    /**
     * Execute the command to add a unique constraint to a table column.
     *
     * @return void
     */
    public function execute()
    {
        // Check for required argument
        if (empty($this->args[0])) {
            $this->error("Usage: gemvc db:unique table/column");
            return;
        }

        // Parse table and column from argument (format: table/column)
        list($table, $column) = explode('/', $this->args[0]);
        if (!$table || !$column) {
            $this->error("Invalid format. Use: gemvc db:unique table/column");
            return;
        }

        // Load environment variables and connect to the database
        ProjectHelper::loadEnv();
        $pdo = DbConnect::connect();
        if (!$pdo) {
            $this->error("Could not connect to database.");
            return;
        }

        // Check for duplicate values in the column
        $stmt = $pdo->query("SELECT `$column`, COUNT(*) as cnt FROM `$table` GROUP BY `$column` HAVING cnt > 1");
        $duplicates = $stmt->fetchAll();
        if ($duplicates) {
            $this->error("Cannot add unique constraint: Duplicate values found in `$column`.");
            foreach ($duplicates as $row) {
                $this->write("Duplicate: " . $row[$column]);
            }
            return;
        }

        // Try to add the unique constraint
        $constraintName = "unique_{$column}";
        try {
            $pdo->exec("ALTER TABLE `$table` ADD CONSTRAINT `$constraintName` UNIQUE (`$column`)");
            $this->success("Unique constraint added to `$table`.`$column` successfully!");
        } catch (\PDOException $e) {
            $this->error("Failed to add unique constraint: " . $e->getMessage());
        }
    }
}
