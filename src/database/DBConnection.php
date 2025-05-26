<?php
namespace Gemvc\Database;

use PDO;
use Gemvc\Helper\TypeHelper;

class DBConnection {
    private bool $isConnected = false;
    private ?string $error = null;
    private ?PDO $db = null;
    private string $instanceId;
    
    public function __construct() {
        $this->instanceId = TypeHelper::guid();
    }
    
    public function connect(): PDO|null {
        if ($this->isConnected) {
            return $this->db;
        }
        if($this->debugMode()){
        // Debug: Print all environment variables
        echo "Debug - All Environment Variables:\n";
        print_r($_ENV);
        
        // Debug: Print specific DB variables
        echo "\nDebug - DB Connection Parameters:\n";
        echo "Host: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
        echo "Port: " . ($_ENV['DB_PORT'] ?? 'not set') . "\n";
        echo "Database: " . ($_ENV['DB_NAME'] ?? 'not set') . "\n";
        echo "Charset: " . ($_ENV['DB_CHARSET'] ?? 'not set') . "\n";
        echo "User: " . ($_ENV['DB_USER'] ?? 'not set') . "\n";
        echo "Password: " . (isset($_ENV['DB_PASSWORD']) ? 'set' : 'not set') . "\n";
        }
        
        // Validate required parameters
        $requiredParams = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_CHARSET', 'DB_USER', 'DB_PASSWORD'];
        $missingParams = [];
        foreach ($requiredParams as $param) {
            if (!isset($_ENV[$param])) {
                $missingParams[] = $param;
            }
        }
        
        if (!empty($missingParams)) {
            $this->error = "Missing required database parameters: " . implode(', ', $missingParams);
                echo "Debug - " . $this->error . "\n";
            return null;
        }
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME'],
            $_ENV['DB_CHARSET']
        );
        if($this->debugMode()){
            echo "Debug - DSN: " . $dsn . "\n";
        }
        
        try {
            echo "Debug - Attempting to create PDO connection...\n";
            $options = [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5, // Add a timeout to prevent hanging
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $_ENV['DB_CHARSET'] // Set charset explicitly
            ];
            
            $this->db = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $options);
            $this->isConnected = true;
            $this->error = null;
            if($this->debugMode()){
                echo "Debug - Connection successful!\n";
            }
            return $this->db;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            echo "Debug - Connection failed: " . $e->getMessage() . "\n";
            echo "Debug - Error code: " . $e->getCode() . "\n";
        }
        return null;
    }
    
    public function isConnected(): bool {
        return $this->isConnected;
    }
    
    public function getError(): ?string {
        return $this->error;
    }
    
    public function db(): PDO|null {
        return $this->db;
    }
    
    public function getInstanceId(): string {
        return $this->instanceId;
    }

    public function debugMode(): bool {
        if($_ENV['APP_ENV'] === 'dev'){
            return true;
        }
        return false;
    }
    
    public function disconnect(): void {
        $this->db = null;
        $this->isConnected = false;
    }
}
