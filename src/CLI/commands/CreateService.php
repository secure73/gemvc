<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Commands\BaseCrudGenerator;

class CreateService extends BaseCrudGenerator
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
            'controller' => false,
            'model' => false,
            'table' => false
        ];

        // Check for combined flags (e.g., -cmt)
        if (isset($this->args[1]) && strpos($this->args[1], '-') === 0) {
            $flagStr = substr($this->args[1], 1);
            $this->flags['controller'] = strpos($flagStr, 'c') !== false;
            $this->flags['model'] = strpos($flagStr, 'm') !== false;
            $this->flags['table'] = strpos($flagStr, 't') !== false;
        }
    }

    public function execute(): void
    {
        if (empty($this->args[0])) {
            $this->error("Service name is required. Usage: gemvc create:service ServiceName [-c|-m|-t]");
        }

        $this->serviceName = $this->formatServiceName($this->args[0]);
        $this->basePath = defined('PROJECT_ROOT') ? PROJECT_ROOT : $this->determineProjectRoot();
        $this->parseFlags();

        try {
            // Create necessary directories
            $this->createDirectories($this->getRequiredDirectories());

            // Create service file
            $this->createService();

            // Create additional files based on flags
            if ($this->flags['controller']) {
                $this->createController();
            }
            if ($this->flags['model']) {
                $this->createModel();
            }
            if ($this->flags['table']) {
                $this->createTable();
            }

            $this->success("Service {$this->serviceName} created successfully!");
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

    protected function createService(): void
    {
        $template = <<<EOT
<?php
/**
 * this is service layer. what so called url end point
 * this layer shall be extended from ApiService class
 * this layer is responsible for handling the request and response
 * this layer is responsible for handling the authentication
 * this layer is responsible for handling the authorization
 * this layer is responsible for handling the validation
 */
namespace App\Api;

use App\Controller\\{$this->serviceName}Controller;
use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class {$this->serviceName} extends ApiService
{
    /**
     * Constructor
     * 
     * @param Request \$request The HTTP request object
     */
    public function __construct(Request \$request)
    {
        parent::__construct(\$request);
    }

    /**
     * Create new {$this->serviceName}
     * 
     * @return JsonResponse
     * @http POST
     * @description Create new {$this->serviceName} in database
     * @example /api/{$this->serviceName}/create
     */
    public function create(): JsonResponse
    {
        \$this->validatePosts([
            'name' => 'string',
            'description' => 'string'
        ]);
        return (new {$this->serviceName}Controller(\$this->request))->create();
    }

    /**
     * Read {$this->serviceName} by ID
     * 
     * @return JsonResponse
     * @http GET
     * @description Get {$this->serviceName} by id from database
     * @example /api/{$this->serviceName}/read/?id=1
     */
    public function read(): JsonResponse
    {
        // empty array define this service accept only get request, no post is allowed
        \$this->validatePosts([]);
        //get the id from the url and if not exist or not type of int return 400 die()
        \$id = \$this->request->intValueGet("id");
        \$this->request->post['id'] = \$id;
        return (new {$this->serviceName}Controller(\$this->request))->read();
    }

    /**
     * Update {$this->serviceName}
     * 
     * @return JsonResponse
     * @http POST
     * @description Update existing {$this->serviceName} in database
     * @example /api/{$this->serviceName}/update
     */
    public function update(): JsonResponse
    {
        \$this->validatePosts([
            'id' => 'int',
            'name' => 'string',
            'description' => 'string'
        ]);
        return (new {$this->serviceName}Controller(\$this->request))->update();
    }

    /**
     * Delete {$this->serviceName}
     * 
     * @return JsonResponse
     * @http POST
     * @description Delete {$this->serviceName} from database
     * @example /api/{$this->serviceName}/delete
     */
    public function delete(): JsonResponse
    {
        \$this->validatePosts(['id' => 'int']);
        return (new {$this->serviceName}Controller(\$this->request))->delete();
    }

    /**
     * List all {$this->serviceName}s
     * 
     * @return JsonResponse
     * @http GET
     * @description Get list of all {$this->serviceName}s with filtering and sorting
     * @example /api/{$this->serviceName}/list/?sort_by=name&find_like=name=test
     */
    public function list(): JsonResponse
    {
        // Define searchable fields and their types
        \$this->request->findable([
            'name' => 'string',
            'description' => 'string'
        ]);

        // Define sortable fields
        \$this->request->sortable([
            'id',
            'name',
            'description'
        ]);
        
        return (new {$this->serviceName}Controller(\$this->request))->list();
    }

    /**
     * Generates mock responses for API documentation
     * 
     * @param string \$method The API method name
     * @return array<mixed> Example response data for the specified method
     * @hidden
     */
    public static function mockResponse(string \$method): array
    {
        return match(\$method) {
            'create' => [
                'response_code' => 201,
                'message' => 'created',
                'count' => 1,
                'service_message' => '{$this->serviceName} created successfully',
                'data' => [
                    'id' => 1,
                    'name' => 'Sample {$this->serviceName}',
                    'description' => '{$this->serviceName} description'
                ]
            ],
            'read' => [
                'response_code' => 200,
                'message' => 'OK',
                'count' => 1,
                'service_message' => '{$this->serviceName} retrieved successfully',
                'data' => [
                    'id' => 1,
                    'name' => 'Sample {$this->serviceName}',
                    'description' => '{$this->serviceName} description'
                ]
            ],
            'update' => [
                'response_code' => 209,
                'message' => 'updated',
                'count' => 1,
                'service_message' => '{$this->serviceName} updated successfully',
                'data' => [
                    'id' => 1,
                    'name' => 'Updated {$this->serviceName}',
                    'description' => 'Updated description'
                ]
            ],
            'delete' => [
                'response_code' => 210,
                'message' => 'deleted',
                'count' => 1,
                'service_message' => '{$this->serviceName} deleted successfully',
                'data' => null
            ],
            'list' => [
                'response_code' => 200,
                'message' => 'OK',
                'count' => 2,
                'service_message' => '{$this->serviceName}s retrieved successfully',
                'data' => [
                    [
                        'id' => 1,
                        'name' => '{$this->serviceName} 1',
                        'description' => 'Description 1'
                    ],
                    [
                        'id' => 2,
                        'name' => '{$this->serviceName} 2',
                        'description' => 'Description 2'
                    ]
                ]
            ],
            default => [
                'success' => false,
                'message' => 'Unknown method'
            ]
        };
    }
}
EOT;

        $path = $this->basePath . "/app/api/{$this->serviceName}.php";
        $this->writeFile($path, $template, "Service");
    }

    protected function createController(): void
    {
        $template = <<<EOT
<?php

namespace App\Controller;

use App\Model\\{$this->serviceName}Model;
use Gemvc\Core\Controller;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class {$this->serviceName}Controller extends Controller
{
    public function __construct(Request \$request)
    {
        parent::__construct(\$request);
    }

    /**
     * Create new {$this->serviceName}
     * 
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        \$model = \$this->request->mapPostToObject(new {$this->serviceName}Model());
        if(!\$model) {
            return \$this->request->returnResponse();
        }
        return \$model->createModel();
    }

    /**
     * Get {$this->serviceName} by ID
     * 
     * @return JsonResponse
     */
    public function read(): JsonResponse
    {
        \$model = \$this->request->mapPostToObject(new {$this->serviceName}Model());
        if(!\$model) {
            return \$this->request->returnResponse();
        }
        return \$model->readModel();
    }

    /**
     * Update existing {$this->serviceName}
     * 
     * @return JsonResponse
     */
    public function update(): JsonResponse
    {
        \$model = \$this->request->mapPostToObject(new {$this->serviceName}Model());
        if(!\$model) {
            return \$this->request->returnResponse();
        }
        return \$model->updateModel();
    }

    /**
     * Delete {$this->serviceName}
     * 
     * @return JsonResponse
     */
    public function delete(): JsonResponse
    {
        \$model = \$this->request->mapPostToObject(new {$this->serviceName}Model());
        if(!\$model) {
            return \$this->request->returnResponse();
        }
        return \$model->deleteModel();
    }

    /**
     * Get list of {$this->serviceName}s with filtering and sorting
     * 
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        \$model = new {$this->serviceName}Model();
        return \$this->createList(\$model);
    }
}
EOT;

        $path = $this->basePath . "/app/controller/{$this->serviceName}Controller.php";
        $this->writeFile($path, $template, "Controller");
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