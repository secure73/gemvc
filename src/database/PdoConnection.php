<?php
namespace Gemvc\Database;

use PDO;

/**
 * To connect Database and provide basic PDO function.
 * @param bool $isConnected
 */
class PdoConnection
{
    private bool $isConnected;
    private ?string $error;
    private ?\PDO $db;

    public function __construct()
    {
        $this->isConnected = false;
        $this->error = null;
        $this->db = null;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * @return null|string
     * if SQL Query executed Successfully , this method return null
     * otherwise return relevant Error string Message
     */
    public function getError(): null|string
    {
        return $this->error;
    }

    /**
     * @return PDO|null
     * in case of failure return false and you can get connection error by getError();
     */
    public function connect(): \Pdo|null
    {
        $dsn__db = 'mysql:host='.$_ENV['DB_HOST'].';port='.$_ENV['DB_PORT'].';dbname='.$_ENV['DB_NAME'].';charset='.$_ENV['DB_CHARSET'];
        try {
            $options__db = [
                \PDO::ATTR_PERSISTENT => true,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ];
            $this->db = new \PDO($dsn__db, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $options__db);
            $this->isConnected = true;
            $this->error  = null;
            return $this->db;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();   
        }
        return null;
    }

    /**
     * @return \PDO
     * Database Connection or null in case of error
     * in case of null , you can see Error  in created instance : $db->error
     */
    public function db(): \PDO|null
    {
        return $this->db;
    }
}
