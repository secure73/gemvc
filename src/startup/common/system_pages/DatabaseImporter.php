<?php
/**
 * Database Importer
 * 
 * Handles importing data into database tables from various formats (CSV, SQL)
 */
class DatabaseImporter
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Import data from CSV file
     */
    public function importCsv(string $tableName, string $filePath): int
    {
        $fileContent = file_get_contents($filePath);
        
        if ($fileContent === false) {
            throw new \Exception('Failed to read CSV file');
        }
        
        // Parse CSV
        $lines = str_getcsv($fileContent, "\n");
        if (empty($lines)) {
            throw new \Exception('CSV file is empty');
        }
        
        // Get headers from first line
        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);
        
        // Prepare INSERT statement
        $placeholders = '(' . implode(', ', array_fill(0, count($headers), '?')) . ')';
        $sql = "INSERT INTO `$tableName` (`" . implode('`, `', $headers) . "`) VALUES $placeholders";
        $stmt = $this->connection->prepare($sql);
        
        // Insert rows
        $imported = 0;
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $stmt->execute($values);
                $imported++;
            }
        }
        
        return $imported;
    }

    /**
     * Import data from SQL file
     */
    public function importSql(string $filePath): void
    {
        $fileContent = file_get_contents($filePath);
        
        if ($fileContent === false) {
            throw new \Exception('Failed to read SQL file');
        }
        
        // Execute SQL file
        $this->connection->exec($fileContent);
    }
}

