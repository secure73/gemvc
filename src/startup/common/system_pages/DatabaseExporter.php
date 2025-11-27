<?php
/**
 * Database Exporter
 * 
 * Handles exporting database tables to various formats (CSV, SQL)
 */
class DatabaseExporter
{
    private \PDO $connection;
    private string $dbName;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        $result = $connection->query("SELECT DATABASE() as db_name");
        $this->dbName = $result->fetch(\PDO::FETCH_ASSOC)['db_name'] ?? '';
    }

    /**
     * Export table to CSV format
     */
    public function exportCsv(string $tableName): void
    {
        $this->validateTable($tableName);
        
        $columns = $this->getTableColumns($tableName);
        $rows = $this->getTableData($tableName);
        
        $filename = $tableName . '_' . date('Y-m-d_H-i-s');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        if (!empty($columns)) {
            fputcsv($output, $columns);
        }
        
        // Write data
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export table structure to SQL format
     */
    public function exportSql(string $tableName): void
    {
        $this->validateTable($tableName);
        
        $escapedTable = '`' . str_replace('`', '``', $tableName) . '`';
        $createTableResult = $this->connection->query("SHOW CREATE TABLE $escapedTable");
        
        if ($createTableResult === false) {
            throw new \Exception("Failed to get table structure");
        }
        
        $createTableRow = $createTableResult->fetch(\PDO::FETCH_ASSOC);
        if (empty($createTableRow) || !isset($createTableRow['Create Table'])) {
            throw new \Exception("Table structure not found");
        }
        
        $filename = $tableName . '_' . date('Y-m-d_H-i-s');
        
        // Set headers for SQL file download
        header('Content-Type: text/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.sql"');
        
        // Get the CREATE TABLE statement and replace with IF NOT EXISTS
        $createTableSql = $createTableRow['Create Table'];
        $createTableSql = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $createTableSql);
        
        // Output SQL with header comment
        $sql = "-- Table Structure: $tableName\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= $createTableSql . ";\n";
        
        echo $sql;
        exit;
    }

    /**
     * Validate that table exists
     */
    private function validateTable(string $tableName): void
    {
        $tableCheck = $this->connection->query("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = " . $this->connection->quote($this->dbName) . " 
            AND TABLE_NAME = " . $this->connection->quote($tableName) . "
            LIMIT 1
        ");
        
        if ($tableCheck->rowCount() === 0) {
            throw new \Exception("Table '$tableName' does not exist");
        }
    }

    /**
     * Get table column names
     */
    private function getTableColumns(string $tableName): array
    {
        $columnsResult = $this->connection->query("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = " . $this->connection->quote($this->dbName) . "
            AND TABLE_NAME = " . $this->connection->quote($tableName) . "
            ORDER BY ORDINAL_POSITION
        ");
        
        $columnsData = $columnsResult->fetchAll(\PDO::FETCH_ASSOC);
        return array_column($columnsData, 'COLUMN_NAME');
    }

    /**
     * Get table data
     */
    private function getTableData(string $tableName): array
    {
        $escapedTable = '`' . str_replace('`', '``', $tableName) . '`';
        $stmt = $this->connection->query("SELECT * FROM $escapedTable");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

