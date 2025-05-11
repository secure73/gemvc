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
    __DIR__ . '/../../autoload.php',                      // When in vendor/gemvc/library/src/bin 
    __DIR__ . '/../../../vendor/autoload.php',            // When in vendor/gemvc/library/src/bin, project root is 3 levels up
    __DIR__ . '/../../../../../autoload.php',             // When in vendor/gemvc/library/src/bin, project vendor is 4 levels up
    __DIR__ . '/../../../../autoload.php'                 // Alternative path
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
    die("Error: Couldn't find Composer autoloader. Make sure you are running this script from the project root.\n");
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
        
        // Common configuration for all platforms
        $envContent = <<<EOT
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
        
        // Add Swoole configuration only if using Swoole platform
        if ($this->platformType === 'swoole') {
            $envContent .= <<<EOT


# OpenSwoole Configuration
SWOOLE_MODE=true
OPENSWOOLE_WORKERS=3

# WebSocket Settings
WS_CONNECTION_TIMEOUT=300
WS_MAX_MESSAGES_PER_MINUTE=60
WS_HEARTBEAT_INTERVAL=30
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
        echo "Setting up local command wrapper...\n";
        
        // Create bin directory if needed
        $binDir = $this->basePath . '/bin';
        if (!is_dir($binDir)) {
            if (!mkdir($binDir, 0755, true)) {
                echo "Warning: Failed to create directory: {$binDir}\n";
                return;
            }
        }
        
        // Create a local wrapper script in the project's bin directory
        $wrapperPath = $binDir . '/gemvc';
        $wrapperContent = <<<EOT
#!/usr/bin/env php
<?php
// Forward to the vendor binary
\$paths = [
    __DIR__ . '/../vendor/bin/gemvc',
    __DIR__ . '/../vendor/gemvc/library/src/bin/gemvc'
];

foreach (\$paths as \$path) {
    if (file_exists(\$path)) {
        require \$path;
        exit;
    }
}

echo "Error: Could not find GEMVC command in vendor directory.\n";
exit(1);
EOT;
        
        if (!file_put_contents($wrapperPath, $wrapperContent)) {
            echo "Warning: Failed to create local wrapper script: {$wrapperPath}\n";
            return;
        }
        
        // Make it executable
        @chmod($wrapperPath, 0755);
        echo "Created local command wrapper: {$wrapperPath}\n";
        
        // Create Windows batch file
        $batPath = $binDir . '/gemvc.bat';
        $batContent = <<<EOT
@echo off
php "%~dp0gemvc" %*
EOT;
        
        if (file_put_contents($batPath, $batContent)) {
            echo "Created Windows batch file: {$batPath}\n";
        }
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
