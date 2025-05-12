#!/usr/bin/env php
<?php
/**
 * GEMVC Project Initialization Script
 * 
 * This non-interactive script initializes a GEMVC project by:
 * - Creating the necessary directory structure
 * - Generating a sample .env file
 * - Copying startup files to the project root
 * - Setting up local command wrappers
 * 
 * Usage: php init.php [apache|swoole]
 */

// Parse command line arguments
$platformType = isset($argv[1]) && in_array(strtolower($argv[1]), ['apache', 'swoole']) 
    ? strtolower($argv[1]) 
    : null;

if (isset($argv[1]) && !in_array(strtolower($argv[1]), ['apache', 'swoole', '--help', '-h'])) {
    echo "Unknown platform type: {$argv[1]}\n";
    echo "Usage: php init.php [apache|swoole]\n";
    exit(1);
}

if (isset($argv[1]) && (strtolower($argv[1]) === '--help' || strtolower($argv[1]) === '-h')) {
    echo "GEMVC Project Initialization Script\n\n";
    echo "Usage: php init.php [platform]\n\n";
    echo "Options:\n";
    echo "  apache    Initialize project with Apache/Nginx configuration\n";
    echo "  swoole    Initialize project with OpenSwoole configuration\n";
    echo "  --help    Display this help message\n";
    echo "\nIf no platform is specified, the script will automatically select\n";
    echo "Apache if available, or fall back to Swoole.\n";
    exit(0);
}

// Include the Composer autoloader - find it at different possible paths
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',                // When in bin directory
    __DIR__ . '/../../vendor/autoload.php',             // When in vendor/gemvc/library/bin
    __DIR__ . '/../../../vendor/autoload.php',          // When in vendor/gemvc/library/bin, project root is 3 levels up
    __DIR__ . '/../../../../vendor/autoload.php'        // Alternative path
];

$autoloaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    echo "\033[31mError: Could not find Composer's autoloader.\033[0m\n";
    echo "Please ensure:\n";
    echo "1. You have run 'composer install'\n";
    echo "2. You are running this script from the project root\n";
    echo "3. The vendor directory exists and is not corrupted\n";
    exit(1);
}

// Check PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "\033[31mError: GEMVC requires PHP 8.0 or higher.\033[0m\n";
    echo "Current PHP version: " . PHP_VERSION . "\n";
    echo "Please upgrade your PHP installation.\n";
    exit(1);
}

// Check required PHP extensions
$requiredExtensions = ['json', 'pdo', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "\033[31mError: Missing required PHP extensions:\033[0m\n";
    foreach ($missingExtensions as $ext) {
        echo "- $ext\n";
    }
    echo "\nPlease install the missing extensions using:\n";
    echo "Windows: Enable them in php.ini\n";
    echo "Linux: apt-get install php8.0-{extension}\n";
    echo "macOS: brew install php@8.0 (includes all required extensions)\n";
    exit(1);
}

/**
 * Non-interactive project initializer
 * 
 * This class completely reinvents project initialization without relying on 
 * the parent class's implementation that requires user interaction
 */
class NonInteractiveInit 
{
    // Define properties needed for initialization
    protected $basePath;
    protected $packagePath;
    protected $platformType;
    
    public function __construct($platformType = null) 
    {
        $this->platformType = $platformType;
    }
    
    public function execute()
    {
        echo "Starting GEMVC project initialization...\n";
        
        // Determine the project root
        $this->basePath = defined('PROJECT_ROOT') ? PROJECT_ROOT : $this->determineProjectRoot();
        echo "Project root: {$this->basePath}\n";
        
        // Determine package path (where the startup templates are located)
        $this->packagePath = $this->determinePackagePath();
        echo "Package path: {$this->packagePath}\n";
        
        try {
            // Create directory structure
            $this->createDirectories();
            
            // Create sample .env file
            $this->createEnvFile();
            
            // Copy startup files to project root
            $this->copyStartupFiles();
            
            // Create modular Swoole structure if needed
            if ($this->platformType === 'swoole') {
                $this->createModularSwooleStructure();
            }
            
            // Secure bin directory in .htaccess for Apache
            if ($this->platformType === 'apache') {
                $this->secureBinDirectory();
            }
            
            // Create or update .gitignore file
            $this->setupGitignore();
            
            // Create global command wrapper
            $this->createGlobalCommand();
            
            echo "\nGEMVC project initialized successfully!\n";
            
            if ($this->platformType) {
                echo "Project initialized with {$this->platformType} configuration.\n";
            }
            
            echo "Run your project with:\n";
            if ($this->platformType === 'swoole') {
                echo "  php index.php\n";
            } else {
                echo "  Point your web server to the project root directory\n";
            }
        } catch (\Exception $e) {
            die("Error: " . $e->getMessage() . "\n");
        }
    }
    
    protected function createDirectories()
    {
        $directories = [
            $this->basePath . '/app',
            $this->basePath . '/app/api',
            $this->basePath . '/app/controller',
            $this->basePath . '/app/model',
            $this->basePath . '/app/table'
        ];
        
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                if (!@mkdir($directory, 0755, true)) {
                    throw new \RuntimeException("Failed to create directory: {$directory}");
                }
                echo "Created directory: {$directory}\n";
            } else {
                echo "Directory already exists: {$directory}\n";
            }
        }
        
        // Create bin directory if it doesn't exist
        if (!is_dir($this->basePath . '/bin')) {
            if (!@mkdir($this->basePath . '/bin', 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$this->basePath}/bin");
            }
            echo "Created directory: {$this->basePath}/bin\n";
        }
    }
    
    protected function createEnvFile()
    {
        $envPath = $this->basePath . '/app/.env';
        
        // Create directory if needed
        $dir = dirname($envPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                die("Error: Failed to create directory: {$dir}\n");
            }
        }
        
        if ($this->platformType === 'swoole') {
            // Specific .env content for Swoole platform
            $envContent = <<<'EOT'
# App Enviroment development or production
APP_ENV=development

# OpenSwoole Configuration
SWOOLE_MODE=true
OPENSWOOLE_WORKERS=3
OPEN_SWOOLE_ACCEPT_REQUEST='0.0.0.0'
OPEN_SWOOLE_ACCPT_PORT=9501

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_db
DB_CHARSET=utf8mb4
DB_USER=root
DB_PASSWORD=''
QUERY_LIMIT=10

# Database Connection Pool
MIN_DB_CONNECTION_POOL=2
#because each worker create its own pool , the MAX_DB_CONNECTION_POOL number shall not be very high!, 
#each connection cost something between 5 to 10 MB from RAM
MAX_DB_CONNECTION_POOL=5
DB_CONNECTION_MAX_AGE=3600

# Security Settings
TOKEN_SECRET='your_secret'
TOKEN_ISSUER='your_api'
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
ACCESS_TOKEN_VALIDATION_IN_SECONDS=15800

# URL Configuration
SERVICE_IN_URL_SECTION=2
METHOD_IN_URL_SECTION=3



# WebSocket Settings
WS_CONNECTION_TIMEOUT=300
WS_MAX_MESSAGES_PER_MINUTE=60
WS_HEARTBEAT_INTERVAL=30
EOT;
        } else {
            // Common configuration for Apache platform
            $envContent = <<<'EOT'
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_db
DB_CHARSET=utf8mb4
DB_USER=root
DB_PASSWORD=''
QUERY_LIMIT=10

# Database Connection Pool
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=10
DB_CONNECTION_MAX_AGE=3600

# Security Settings
TOKEN_SECRET='your_secret'
TOKEN_ISSUER='your_api'
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
ACCESS_TOKEN_VALIDATION_IN_SECONDS=15800

# URL Configuration
SERVICE_IN_URL_SECTION=2
METHOD_IN_URL_SECTION=3
EOT;
        }
        
        if (!file_put_contents($envPath, $envContent)) {
            die("Error: Failed to create .env file: {$envPath}\n");
        }
        
        echo "Created .env file: {$envPath}\n";
    }
    
    protected function copyStartupFiles()
    {
        // Try multiple possible paths for startup templates
        $potentialPaths = [
            $this->packagePath . '/src/startup',
            $this->packagePath . '/startup',
            dirname(dirname(__DIR__)) . '/startup'
        ];
        
        $startupPath = null;
        foreach ($potentialPaths as $path) {
            if (file_exists($path) && is_dir($path)) {
                $startupPath = $path;
                echo "Found startup directory: {$startupPath}\n";
                break;
            }
        }
        
        if ($startupPath === null) {
            die("Error: Startup directory not found. Tried: " . implode(", ", $potentialPaths) . "\n");
        }
        
        // Check if this directory has subdirs for different templates
        $templateDirs = [];
        $dirs = scandir($startupPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            if (is_dir($startupPath . '/' . $dir)) {
                $templateDirs[] = $dir;
                echo "Found template: {$dir}\n";
            }
        }
        
        // Choose template based on parameter or auto-select
        $templateDir = $startupPath;
        if (!empty($templateDirs)) {
            // Use specified platform if provided
            if ($this->platformType && in_array($this->platformType, $templateDirs)) {
                $templateDir = $startupPath . '/' . $this->platformType;
                echo "Using template directory: {$this->platformType} (user specified)\n";
            } 
            // Auto-select based on priority
            else {
                if (in_array('apache', $templateDirs) && (!$this->platformType || $this->platformType === 'apache')) {
                    $templateDir = $startupPath . '/apache';
                } elseif (in_array('swoole', $templateDirs) && (!$this->platformType || $this->platformType === 'swoole')) {
                    $templateDir = $startupPath . '/swoole';
                } else {
                    $templateDir = $startupPath . '/' . $templateDirs[0];
                }
                echo "Using template directory: " . basename($templateDir) . " (auto-selected)\n";
            }
        } else {
            echo "No specific template directories found, using startup directory directly\n";
        }
        
        if (!is_dir($templateDir)) {
            die("Error: Template directory not found: {$templateDir}\n");
        }
        
        // Copy all files from template dir
        $files = scandir($templateDir);
        $copiedCount = 0;
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourcePath = $templateDir . '/' . $file;
            $destPath = $this->basePath . '/' . $file;
            
            // Skip directories, we just want files
            if (is_dir($sourcePath)) {
                continue;
            }
            
            // Always overwrite existing files
            if (!copy($sourcePath, $destPath)) {
                echo "Warning: Failed to copy file: {$file}\n";
                continue;
            }
            
            echo "Copied: {$file}\n";
            $copiedCount++;
        }
        
        if ($copiedCount === 0) {
            echo "Warning: No template files were copied. Check if the template directory contains files.\n";
        } else {
            echo "Successfully copied {$copiedCount} template files.\n";
        }
    }
    
    protected function createGlobalCommand()
    {
        echo "Setting up command structure...\n";
        
        // Create bin directory if needed
        $binDir = $this->basePath . '/bin';
        if (!is_dir($binDir)) {
            if (!mkdir($binDir, 0755, true)) {
                echo "Warning: Failed to create directory: {$binDir}\n";
                return;
            }
        }

        // Create the CLI commands directory structure
        $cliDir = $this->basePath . '/app/CLI/commands';
        if (!is_dir($cliDir)) {
            if (!mkdir($cliDir, 0755, true)) {
                echo "Warning: Failed to create directory: {$cliDir}\n";
                return;
            }
        }

        // Create a README for custom commands
        $readmeContent = <<<'EOT'
# Custom CLI Commands

This directory contains your project-specific CLI commands.

## Creating a New Command

1. Create a new PHP class in this directory
2. Extend the `Gemvc\CLI\Command` class
3. Implement the `execute()` method

Example:

```php
<?php

namespace App\CLI\Commands;

use Gemvc\CLI\Command;

class MyCustomCommand extends Command
{
    public function execute()
    {
        // Your command logic here
        $this->info("Hello from my custom command!");
    }
}
```

Usage: `gemvc my:custom`
EOT;

        file_put_contents($cliDir . '/README.md', $readmeContent);

        // Create the main command executor
        $mainScript = <<<'EOT'
#!/usr/bin/env php
<?php

/**
 * GEMVC Command Line Interface
 * 
 * This is the main entry point for all GEMVC commands, both framework and project specific.
 * It handles command discovery and execution from both the framework and the project.
 */

// Determine if we're in a project context or framework context
$possibleAutoloaders = [
    __DIR__ . '/../vendor/autoload.php',              // Project installation
    __DIR__ . '/../autoload.php',                     // Framework development
    __DIR__ . '/../../autoload.php',                  // Alternative project path
    __DIR__ . '/../../../../autoload.php'             // Composer vendor installation
];

$autoloaded = false;
foreach ($possibleAutoloaders as $autoloader) {
    if (file_exists($autoloader)) {
        require_once $autoloader;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    echo "\033[31mError: Could not find Composer's autoloader.\033[0m\n";
    echo "Please ensure:\n";
    echo "1. You have run 'composer install'\n";
    echo "2. You are running this script from the project root\n";
    echo "3. The vendor directory exists and is not corrupted\n";
    exit(1);
}

// Set up command paths
$paths = [
    'framework' => [
        'commands' => dirname(__DIR__) . '/src/CLI/commands',
        'namespace' => 'Gemvc\\CLI\\Commands\\'
    ],
    'project' => [
        'commands' => getcwd() . '/app/CLI/commands',
        'namespace' => 'App\\CLI\\Commands\\'
    ]
];

// Parse command line arguments
$command = $argv[1] ?? '--help';
$args = array_slice($argv, 2);

// Convert command format (e.g., create:service -> CreateService)
$commandParts = explode(':', $command);
$className = '';
foreach ($commandParts as $part) {
    $className .= ucfirst(strtolower($part));
}
$className .= 'Command';

// Try to find and execute the command
$commandFound = false;

foreach ($paths as $context) {
    $commandClass = $context['namespace'] . $className;
    
    if (class_exists($commandClass)) {
        try {
            $commandObj = new $commandClass($args);
            $commandObj->execute();
            $commandFound = true;
            break;
        } catch (\Exception $e) {
            echo "\033[31mError: {$e->getMessage()}\033[0m\n";
            exit(1);
        }
    }
}

if (!$commandFound) {
    if ($command === '--help' || $command === '-h') {
        echo "GEMVC Framework CLI\n\n";
        echo "Usage: gemvc <command> [options]\n\n";
        echo "Available commands:\n";
        echo "  init                          Initialize GEMVC project structure\n";
        echo "  setup [apache|swoole]         Configure project for Apache or OpenSwoole\n";
        echo "  create:service <ServiceName>  Create a new service\n";
        echo "  create:model <ModelName>      Create a new model\n";
        echo "  create:table <TableName>      Create a new table class\n";
        echo "  --help                        Show this help message\n";
    } else {
        echo "\033[31mError: Command '$command' not found.\033[0m\n";
        echo "Run 'gemvc --help' for available commands.\n";
        exit(1);
    }
}
EOT;

        $executablePath = $binDir . '/gemvc';
        if (!file_put_contents($executablePath, $mainScript)) {
            echo "Warning: Failed to create command executor: {$executablePath}\n";
            return;
        }
        chmod($executablePath, 0755);

        // Create Windows batch file
        $batContent = "@echo off\nphp \"%~dp0gemvc\" %*";
        $batPath = $binDir . '/gemvc.bat';
        if (file_put_contents($batPath, $batContent)) {
            echo "Created Windows batch file: {$batPath}\n";
        }

        echo "Command structure set up successfully!\n";
        echo "You can now use 'bin/gemvc' to run commands.\n";
        echo "Add the bin directory to your PATH for global access.\n";
    }
    
    /**
     * Secure bin directory to prevent direct access
     */
    protected function secureBinDirectory()
    {
        // Create bin directory if it doesn't exist
        $binDir = $this->basePath . '/bin';
        if (!is_dir($binDir)) {
            if (!@mkdir($binDir, 0755, true)) {
                echo "Warning: Failed to create directory: {$binDir}\n";
                return;
            }
        }

        // For Apache platform, add protection to .htaccess
        if ($this->platformType === 'apache') {
            $htaccessPath = $this->basePath . '/.htaccess';
            
            if (!file_exists($htaccessPath)) {
                echo "Warning: .htaccess file not found. Creating a basic .htaccess file.\n";
                
                $basicHtaccess = <<<EOT
<Directory "vendor">
    Require all denied
</Directory>

EOT;
                file_put_contents($htaccessPath, $basicHtaccess);
            }
            
            $htaccessContent = file_get_contents($htaccessPath);
            
            // Check if the bin directory is already secured
            if (strpos($htaccessContent, '<Directory "bin">') !== false) {
                echo "Bin directory already secured in .htaccess\n";
            } else {
                // Find where to insert the bin directory protection
                $vendorDirective = '<Directory "vendor">';
                $binDirective = <<<EOT

<Directory "bin">
    Require all denied
</Directory>
EOT;
                
                if (strpos($htaccessContent, $vendorDirective) !== false) {
                    // Add bin directory protection after vendor directory protection
                    $htaccessContent = str_replace(
                        "</Directory>\n",
                        "</Directory>\n" . $binDirective,
                        $htaccessContent
                    );
                } else {
                    // Add bin directory protection at the end
                    $htaccessContent .= $binDirective . "\n";
                }
                
                // Update the .htaccess file
                if (file_put_contents($htaccessPath, $htaccessContent)) {
                    echo "Secured bin directory in .htaccess\n";
                } else {
                    echo "Warning: Failed to update .htaccess file\n";
                }
            }
        }
        
        // For both platforms, add an index.php file to bin directory that prevents direct access
        $indexBlocker = <<<'EOT'
<?php
// Prevent direct access to bin directory
http_response_code(403);
echo json_encode([
    'response_code' => 403,
    'message' => 'Access Forbidden',
    'data' => null
]);
exit;
EOT;

        $indexPath = $binDir . '/index.php';
        if (file_put_contents($indexPath, $indexBlocker)) {
            echo "Added protection index.php to bin directory\n";
        } else {
            echo "Warning: Failed to create protection index.php in bin directory\n";
        }

        // Also create .htaccess in the bin directory itself as an additional layer of protection
        $binHtaccess = "Require all denied\n";
        $binHtaccessPath = $binDir . '/.htaccess';
        if (file_put_contents($binHtaccessPath, $binHtaccess)) {
            echo "Created .htaccess in bin directory for additional protection\n";
        } else {
            echo "Warning: Failed to create .htaccess in bin directory\n";
        }
    }
    
    /**
     * Set up .gitignore file to exclude common directories and files
     */
    protected function setupGitignore()
    {
        $gitignorePath = $this->basePath . '/.gitignore';
        $commonExcludes = [
            'vendor/',
            'node_modules/',
            '.env',
            '.DS_Store',
            '.idea/',
            '.vscode/',
            '*.log',
            'app/.env'
        ];
        
        // Read existing .gitignore if it exists
        $existingContent = file_exists($gitignorePath) ? file_get_contents($gitignorePath) : '';
        $existingLines = $existingContent ? explode("\n", $existingContent) : [];
        
        $updatedContent = '';
        $updated = false;
        
        // Check if vendor directory is already excluded
        $vendorExcluded = false;
        foreach ($existingLines as $line) {
            if (trim($line) === 'vendor/' || trim($line) === '/vendor/' || trim($line) === 'vendor' || trim($line) === '/vendor') {
                $vendorExcluded = true;
                break;
            }
        }
        
        if ($existingContent) {
            // Update existing .gitignore
            if (!$vendorExcluded) {
                // Add vendor/ to the beginning to ensure it's excluded
                $updatedContent = "vendor/\n" . $existingContent;
                $updated = true;
            } else {
                // If vendor is already excluded, keep the file as is
                $updatedContent = $existingContent;
            }
        } else {
            // Create new .gitignore with common excludes
            $updatedContent = implode("\n", $commonExcludes);
            $updated = true;
        }
        
        // Write the updated content
        if ($updated) {
            if (file_put_contents($gitignorePath, $updatedContent)) {
                echo "Updated .gitignore to exclude vendor directory\n";
            } else {
                echo "Warning: Failed to update .gitignore file\n";
            }
        } else {
            echo "Vendor directory already excluded in .gitignore\n";
        }
    }
    
    /**
     * Create a modular file structure for Swoole
     * 
     * This creates a modular directory structure for Swoole with environment variables
     * 
     * @return void
     */
    protected function createModularSwooleStructure()
    {
        if ($this->platformType !== 'swoole') {
            return;
        }
        
        echo "Creating modular Swoole structure...\n";
        
        // Create the server directory for Swoole modules
        $serverDir = $this->basePath . '/server';
        if (!is_dir($serverDir)) {
            mkdir($serverDir, 0755, true);
        }
        
        // Create subdirectories for organization
        $dirs = ['config', 'handlers', 'utils'];
        foreach ($dirs as $dir) {
            $path = $serverDir . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
        
        // Create a simplified index.php
        $simpleIndex = <<<'EOT'
<?php
/**
 * OpenSwoole Server Entry Point for GEMVC Framework
 *
 * This is the main entry point for the OpenSwoole server.
 * The actual server logic is organized into modular files in the /server directory.
 */

// Load the server bootstrap
require_once __DIR__ . '/server/bootstrap.php';

// Start the server
\Server\Bootstrap::start();
EOT;

        file_put_contents($this->basePath . '/index.php', $simpleIndex);
        echo "Created simplified index.php\n";

        // Create bootstrap.php
        $bootstrap = <<<'EOT'
<?php

namespace Server;

// Load configuration and component files
require_once __DIR__ . '/config/server.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/handlers/request.php';
require_once __DIR__ . '/handlers/worker.php';

/**
 * Server Bootstrap
 * 
 * This class initializes and starts the OpenSwoole server.
 */
class Bootstrap
{
    /**
     * Start the server
     */
    public static function start()
    {
        // Check for OpenSwoole or regular Swoole extension
        if (!extension_loaded('openswoole') && !extension_loaded('swoole')) {
            throw new \Exception('Neither OpenSwoole nor Swoole extensions are installed. Please install one with: pecl install openswoole');
        }

        // Required dependencies
        require_once 'vendor/autoload.php';

        // Load environment variables
        $dotenv = new \Symfony\Component\Dotenv\Dotenv();
        $dotenv->load(__DIR__ . '/../app/.env');

        // Development mode detection
        $isDev = getenv('APP_ENV') === 'development' || !getenv('APP_ENV');
        echo $isDev ? "Running in DEVELOPMENT mode\n" : "Running in PRODUCTION mode\n";

        // Define the server class based on available extension
        if (extension_loaded('openswoole')) {
            $serverClass = '\OpenSwoole\HTTP\Server';
            $timerClass = '\OpenSwoole\Timer';
        } else {
            $serverClass = '\Swoole\HTTP\Server';
            $timerClass = '\Swoole\Timer';
        }

        // Get server IP and port from environment variables with fallbacks
        $serverHost = getenv('OPEN_SWOOLE_ACCEPT_REQUEST') ?: '0.0.0.0';
        $serverPort = (int)(getenv('OPEN_SWOOLE_ACCPT_PORT') ?: 9501);

        // Create HTTP Server
        $server = new $serverClass($serverHost, $serverPort);
        echo "Server configured to listen on {$serverHost}:{$serverPort}\n";

        // Apply server configuration from environment settings
        $workers = (int)(getenv('OPENSWOOLE_WORKERS') ?: ($isDev ? 1 : 4));
        
        // Set server configurations
        $server->set([
            'worker_num' => $workers,
            'max_request' => $isDev ? 1 : 1000,
            'enable_coroutine' => true,
            'document_root' => __DIR__ . '/..',  // Project root
            'enable_static_handler' => true,
            'static_handler_locations' => ['/public', '/assets'], // Only serve files from these directories
        ]);

        // Register event handlers
        Handlers\registerWorkerEvents($server, $isDev, $timerClass);
        Handlers\registerRequestHandler($server);

        // Only preload files in production for better performance
        if (!$isDev) {
            echo "Production mode detected. Preloading files...\n";
            Utils\preloadFiles();
        } else {
            echo "Development mode detected. Skipping preload for faster development cycles.\n";
        }

        // Start the server
        echo "Starting OpenSwoole server on {$serverHost}:{$serverPort}...\n";
        $server->start();
    }
}
EOT;

        file_put_contents($serverDir . '/bootstrap.php', $bootstrap);
        echo "Created server/bootstrap.php\n";

        // Create worker handlers
        $workerHandlers = <<<'EOT'
<?php

namespace Server\Handlers;

/**
 * Register worker-related event handlers
 * 
 * @param mixed $server The Swoole server instance
 * @param bool $isDev Whether in development mode
 * @param string $timerClass The timer class to use
 * @return void
 */
function registerWorkerEvents($server, bool $isDev, $timerClass): void
{
    // Store file hashes for hot reload
    $fileHashes = [];

    // Enable hot reload in development mode
    if ($isDev) {
        // Setup a file watcher that runs every second
        $timerClass::tick(1000, function() use (&$fileHashes, $server) {
            $dirs = ['app/api', 'app/controller', 'app/model', 'app/core', 'app/http', 'app/table'];
            $changed = false;
            
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) continue;
                
                $files = glob("$dir/*.php");
                foreach ($files as $file) {
                    $currentHash = md5_file($file);
                    if (!isset($fileHashes[$file]) || $fileHashes[$file] !== $currentHash) {
                        echo "[Hot Reload] File changed: $file\n";
                        $fileHashes[$file] = $currentHash;
                        $changed = true;
                    }
                }
            }
            
            if ($changed) {
                echo "[Hot Reload] Reloading workers...\n";
                $server->reload(); // Reload all worker processes
            }
        });
    }

    // Worker start event
    $server->on('workerStart', function($server, $workerId) use ($isDev) {
        echo "Worker #$workerId started\n";
        
        // Clear opcache on worker start in development mode (if enabled)
        if ($isDev && function_exists('opcache_reset')) {
            opcache_reset();
        }
    });

    // Worker stop event (graceful shutdown)
    $server->on('workerStop', function($server, $workerId) {
        echo "Worker #$workerId stopping...\n";
        // Perform any needed cleanup here
    });
}
EOT;

        file_put_contents($serverDir . '/handlers/worker.php', $workerHandlers);
        echo "Created server/handlers/worker.php\n";

        // Create request handler
        $requestHandler = <<<'EOT'
<?php

namespace Server\Handlers;

use App\Core\SwooleBootstrap;
use Gemvc\Http\SwooleRequest;
use Gemvc\Http\NoCors;

/**
 * Register the main request handler
 * 
 * @param mixed $server The Swoole server instance
 * @return void
 */
function registerRequestHandler($server): void
{
    $server->on('request', function ($request, $response) {
        // Block direct access to app directory
        $path = parse_url($request->server['request_uri'], PHP_URL_PATH);
        if (preg_match('#^/app/#', $path)) {
            $response->status(403);
            $response->end(json_encode([
                'response_code' => 403,
                'message' => 'Access Forbidden',
                'data' => null
            ]));
            return;
        }
        
        try {
            // Apply CORS headers
            NoCors::NoCors();
            
            // Create GEMVC request from Swoole request
            $webserver = new SwooleRequest($request);
            
            // Process the request
            $bootstrap = new SwooleBootstrap($webserver->request);
            $jsonResponse = $bootstrap->processRequest();
            
            // Capture the response using output buffering
            ob_start();
            $jsonResponse->show();
            $jsonContent = ob_get_clean();
            
            // Set content type header
            $response->header('Content-Type', 'application/json');
            
            // Send the JSON response
            $response->end($jsonContent);
            
        } catch (\Throwable $e) {
            // Handle any exceptions
            $errorResponse = [
                'response_code' => 500,
                'message' => 'Internal Server Error',
                'service_message' => $e->getMessage(),
                'data' => null
            ];
            
            // Set content type header
            $response->header('Content-Type', 'application/json');
            
            // Send the error response
            $response->end(json_encode($errorResponse));
        }
    });
}
EOT;

        file_put_contents($serverDir . '/handlers/request.php', $requestHandler);
        echo "Created server/handlers/request.php\n";

        // Create helper functions
        $helpers = <<<'EOT'
<?php

namespace Server\Utils;

/**
 * Utility functions for the Swoole server
 */

/**
 * Preload application files into memory for better performance
 * 
 * @return int Number of files preloaded
 */
function preloadFiles(): int {
    echo "Preloading application files...\n";
    $directories = [
        'app/api',
        'app/controller',
        'app/model',
        'app/core',
        'app/http',
        'app/table',
        'vendor/gemvc'
    ];
    
    $count = 0;
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            echo "Directory not found: $dir\n";
            continue;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                try {
                    require_once $file->getPathname();
                    $count++;
                } catch (\Throwable $e) {
                    echo "Error loading file {$file->getPathname()}: {$e->getMessage()}\n";
                }
            }
        }
    }
    
    echo "Preloaded $count PHP files\n";
    return $count;
}
EOT;

        file_put_contents($serverDir . '/utils/helpers.php', $helpers);
        echo "Created server/utils/helpers.php\n";

        echo "Modular Swoole structure created successfully!\n";
    }
    
    protected function determineProjectRoot(): string
    {
        // Try to find composer.json to identify project root
        $dir = getcwd();
        while ($dir) {
            if (file_exists($dir . '/composer.json')) {
                return $dir;
            }
            
            $parentDir = dirname($dir);
            if ($parentDir === $dir) {
                break; // Reached root directory
            }
            $dir = $parentDir;
        }
        
        // Fallback to current directory
        return getcwd() ?: '.';
    }
    
    protected function determinePackagePath(): string
    {
        // Multiple possible locations for package path
        $paths = [
            dirname(dirname(__DIR__)), // src/bin -> src
            dirname(dirname(dirname(__DIR__))), // If src/bin is inside another directory
            dirname(dirname(dirname(dirname(dirname(__DIR__))))).'/gemvc/library', // vendor/gemvc/library
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Fallback
        return dirname(dirname(__DIR__)); // Hope for the best
    }
}

// Run the non-interactive init script with the specified platform type
$init = new NonInteractiveInit($platformType);
$init->execute();
