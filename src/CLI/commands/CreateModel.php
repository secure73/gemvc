<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Commands\BaseCrudGenerator;

class CreateModel extends BaseCrudGenerator
{
    protected $serviceName;
    protected $basePath;
    protected $flags = [];

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

    /**
     * Parse command line flags
     * 
     * @return void
     */
    protected function parseFlags(): void
    {
        $this->flags = [
            'table' => false
        ];

        // Check for table flag
        if (isset($this->args[1]) && strpos($this->args[1], '-') === 0) {
            $flagStr = substr($this->args[1], 1);
            $this->flags['table'] = strpos($flagStr, 't') !== false;
        }
    }

    public function execute(): void
    {
        if (empty($this->args[0])) {
            $this->error("Model name is required. Usage: gemvc create:model ModelName [-t]");
        }

        $this->serviceName = $this->formatServiceName($this->args[0]);
        $this->basePath = defined('PROJECT_ROOT') ? PROJECT_ROOT : $this->determineProjectRoot();
        $this->parseFlags();

        try {
            // Create necessary directories
            $this->createDirectories($this->getRequiredDirectories());

            // Create model file
            $this->createModel();

            // Create table file if flag is set
            if ($this->flags['table']) {
                $this->createTable();
            }

            $this->success("Model {$this->serviceName} created successfully!");
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

    protected function createModel(): void
    {
        $template = <<<EOT
<?php
/**
 * this is model layer. what so called Data logic layer
 * classes in this layer shall be extended from relevant classes in Table layer
 * classes in this layer  will be called from controller layer
 */
namespace App\Model;

use App\Table\\{$this->serviceName}Table;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;

class {$this->serviceName}Model extends {$this->serviceName}Table
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create new {$this->serviceName}
     * 
     * @return JsonResponse
     */
    public function createModel(): JsonResponse
    {
        \$success = \$this->insertSingleQuery();
        if (\$this->getError()) {
            return Response::internalError("Failed to create {$this->serviceName}:" . \$this->getError());
        }
        return Response::created(\$success, 1, "{$this->serviceName} created successfully");
    }

    /**
     * Get {$this->serviceName} by ID
     * 
     * @return JsonResponse
     */
    public function readModel(): JsonResponse
    {
        \$item = \$this->selectById(\$this->id);
        if (!\$item) {
            return Response::notFound("{$this->serviceName} not found");
        }
        return Response::success(\$item, 1, "{$this->serviceName} retrieved successfully");
    }

    /**
     * Update existing {$this->serviceName}
     * 
     * @return JsonResponse
     */
    public function updateModel(): JsonResponse
    {
        \$item = \$this->selectById(\$this->id);
        if (!\$item) {
            return Response::notFound("{$this->serviceName} not found");
        }
        \$success = \$this->updateSingleQuery();
        if (\$this->getError()) {
            return Response::internalError("Failed to update {$this->serviceName}:" . \$this->getError());
        }
        return Response::updated(\$success, 1, "{$this->serviceName} updated successfully");
    }

    /**
     * Delete {$this->serviceName}
     * 
     * @return JsonResponse
     */
    public function deleteModel(): JsonResponse
    {
        \$item = \$this->selectById(\$this->id);
        if (!\$item) {
            return Response::notFound("{$this->serviceName} not found");
        }
        \$success = \$this->deleteByIdQuery(\$this->id);
        if (\$this->getError()) {
            return Response::internalError("Failed to delete {$this->serviceName}:" . \$this->getError());
        }
        return Response::deleted(\$success, 1, "{$this->serviceName} deleted successfully");
    }
}
EOT;

        $path = $this->basePath . "/app/model/{$this->serviceName}Model.php";
        $this->writeFile($path, $template, "Model");
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