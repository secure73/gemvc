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
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME'],
            $_ENV['DB_CHARSET']
        );
        
        try {
            $options = [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];
            
            $this->db = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $options);
            $this->isConnected = true;
            $this->error = null;
            return $this->db;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();   
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
    
    public function disconnect(): void {
        $this->db = null;
        $this->isConnected = false;
    }
}
