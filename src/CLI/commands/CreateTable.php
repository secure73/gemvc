<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Commands\BaseCrudGenerator;

class CreateTable extends BaseCrudGenerator
{
    protected $serviceName;
    protected $basePath;

    /**
     * Format service name to proper case
     * 
     * @param string $name
     * @return string
     */
    protected function formatServiceName(string $name): string
    {
        return ucfirst(strtolower($name));
    }

    public function execute(): void
    {
        if (empty($this->args[0])) {
            $this->error("Table name is required. Usage: gemvc create:table TableName");
        }

        $this->serviceName = $this->formatServiceName($this->args[0]);
        $this->basePath = defined('PROJECT_ROOT') ? PROJECT_ROOT : $this->determineProjectRoot();

        try {
            // Create necessary directories
            $this->createDirectories($this->getRequiredDirectories());

            // Create table file
            $this->createTable();

            $this->success("Table {$this->serviceName} created successfully!");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function createDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                if (!@mkdir($directory, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$directory}");
                }
                $this->info("Created directory: {$directory}");
            }
        }
    }

    protected function confirmOverwrite(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }
        
        echo "File already exists: {$path}" . PHP_EOL;
        echo "Do you want to overwrite it? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        return strtolower(trim($line)) === 'y';
    }

    protected function writeFile(string $path, string $content, string $fileType): void
    {
        if (!$this->confirmOverwrite($path)) {
            $this->info("Skipped {$fileType}: " . basename($path));
            return;
        }

        if (!file_put_contents($path, $content)) {
            $this->error("Failed to create {$fileType} file: {$path}");
        }
        $this->info("Created {$fileType}: " . basename($path));
    }

    protected function createTable(): void
    {
        $tableName = strtolower($this->serviceName) . 's';
        
        $template = <<<EOT
<?php
/**
 * this is table layer. what so called Data access layer
 * classes in this layer shall be extended from CRUDTable or Gemvc\Core\Table ;
 * for each column in database table, you must define property in this class with same name and property type;
 */
namespace App\Table;

use Gemvc\Database\Table;

/**
 * {$this->serviceName} table class for handling {$this->serviceName} database operations
 * 
 * @property int \$id {$this->serviceName}'s unique identifier column id in database table
 * @property string \$name {$this->serviceName}'s name column name in database table
 * @property string \$description {$this->serviceName}'s description column description in database table
 */
class {$this->serviceName}Table extends Table
{
    public int \$id;
    public string \$name;
    public string \$description;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return string
     * the name of the database table
     */
    public function getTable(): string
    {
        //return the name of the table in database
        return '{$tableName}';
    }

    /**
     * @return null|static
     * null or {$this->serviceName}Table Object
     */
    public function selectById(int \$id): null|static
    {
        \$result = \$this->select()->where('id', \$id)->limit(1)->run();
        return \$result[0] ?? null;
    }

    /**
     * @return null|static[]
     * null or array of {$this->serviceName}Table Objects
     */
    public function selectByName(string \$name): null|array
    {
        return \$this->select()->whereLike('name', \$name)->run();
    }
}
EOT;

        $path = $this->basePath . "/app/table/{$this->serviceName}Table.php";
        $this->writeFile($path, $template, "Table");
    }

    protected function determineProjectRoot(): string
    {
        // Start with composer's vendor directory (where this file is located)
        $vendorDir = dirname(dirname(dirname(dirname(__DIR__))));
        
        // If we're in the vendor directory, the project root is one level up
        if (basename($vendorDir) === 'vendor') {
            return dirname($vendorDir);
        }
        
        // Fallback to current directory if we can't determine project root
        return getcwd() ?: '.';
    }
} 