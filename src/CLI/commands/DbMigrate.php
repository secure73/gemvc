<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\CLI\Commands\DbConnect;
use Gemvc\Database\TableGenerator;
use Gemvc\Database\SchemaGenerator;
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
    - Manage indexes
    - Apply schema constraints (unique, foreign keys, indexes, etc.)
    - Remove obsolete constraints (with --sync-schema flag)";

    public function execute()
    {
        try {
            ProjectHelper::loadEnv();
            $pdo = DbConnect::connect();
            if (!$pdo) {
                return;
            }

            if (empty($this->args[0])) {
                $this->error("Table class name is required. Usage: gemvc db:migrate TableClassName [--force] [--sync-schema]");
                return;
            }

            $tableClass = $this->args[0];
            $force = in_array('--force', $this->args);
            $enforceNotNull = in_array('--enforce-not-null', $this->args);
            $syncSchema = in_array('--sync-schema', $this->args);

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
            $generator = new TableGenerator($pdo);

            // First check if table exists
            $tableName = $table->getTable();
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
            $tableExists = $stmt->rowCount() > 0;

            if ($tableExists) {
                $this->info("Table '{$tableName}' exists. Syncing with class definition...");
                if ($force) {
                    $this->info("Force flag detected. Will remove columns not in class definition.");
                }
                if ($syncSchema) {
                    $this->info("Schema sync enabled. Will remove obsolete constraints.");
                }
                if ($generator->updateTable($table, null, $force, $enforceNotNull, $defaultValue)) {
                    $this->success("Table '{$tableName}' synchronized successfully!");
                    
                    // Apply schema constraints after table update
                    $this->applySchemaConstraints($pdo, $table, $tableName, $syncSchema);
                } else {
                    $this->error("Failed to sync table: " . $generator->getError());
                }
            } else {
                $this->info("Table '{$tableName}' does not exist. Creating new table...");
                if ($generator->createTableFromObject($table)) {
                    $this->success("Table '{$tableName}' created successfully!");
                    
                    // Apply schema constraints after table creation
                    $this->applySchemaConstraints($pdo, $table, $tableName, $syncSchema);
                } else {
                    $this->error("Failed to create table: " . $generator->getError());
                }
            }
        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
        }
    }

    /**
     * Apply schema constraints using the SchemaGenerator
     * 
     * @param \PDO $pdo Database connection
     * @param object $table Table instance
     * @param string $tableName Table name
     * @param bool $syncSchema Whether to remove obsolete constraints
     */
    private function applySchemaConstraints(\PDO $pdo, object $table, string $tableName, bool $syncSchema = false): void
    {
        // Check if table has schema constraints defined
        if (!method_exists($table, 'defineSchema')) {
            return;
        }

        $this->info("Processing schema constraints...");
        $schemaDefinition = $table->defineSchema();
        
        if (empty($schemaDefinition) && !$syncSchema) {
            $this->info("No schema constraints defined.");
            return;
        }

        // Create SchemaGenerator instance
        $schemaGenerator = new SchemaGenerator($pdo, $tableName, $schemaDefinition);
        
        // Apply constraints (with optional removal of obsolete ones)
        if ($schemaGenerator->applyConstraints($syncSchema)) {
            $summary = $schemaGenerator->getSummary();
            
            if ($summary['total_constraints'] > 0) {
                $this->success("Applied {$summary['total_constraints']} schema constraints successfully!");
                
                // Show details of applied constraints
                foreach ($summary['constraint_types'] as $type => $count) {
                    $this->info("  ✓ {$count} {$type} constraint(s)");
                }
            } else if ($syncSchema) {
                $this->info("Schema synchronized successfully (no constraints to add).");
            } else {
                $this->info("No schema constraints to apply.");
            }
        } else {
            $this->error("Failed to apply schema constraints: " . $schemaGenerator->getError());
        }
    }
} 