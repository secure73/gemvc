<?php

namespace Gemvc\CLI\Commands;

use Gemvc\CLI\Command;
use Gemvc\Database\DBConnection;
use Gemvc\Helper\ProjectHelper;
use PDO;

class DbInit extends Command
{
    public function execute()
    {
        try {
            $this->info("Initializing database...");
            
            // Load environment variables
            ProjectHelper::loadEnv();
            
            // Get database configuration from environment
            $dbName = $_ENV['DB_NAME'] ?? null;
            $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
            $dbUser = $_ENV['DB_USER'] ?? 'root';
            $dbPass = $_ENV['DB_PASSWORD'] ?? '';
            $dbPort = $_ENV['DB_PORT'] ?? 3306;
            $dbCharset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
            
            if (!$dbName) {
                throw new \Exception("Database name not found in configuration (DB_NAME)");
            }
            
            // Create connection without database name
            $dsn = sprintf(
                'mysql:host=%s;port=%s;charset=%s',
                $dbHost,
                $dbPort,
                $dbCharset
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbCharset}"
            ];
            
            $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            
            // Create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`");
            
            $this->success("Database '{$dbName}' initialized successfully!");
            
        } catch (\Exception $e) {
            $this->error("Failed to initialize database: " . $e->getMessage());
        }
    }
} 