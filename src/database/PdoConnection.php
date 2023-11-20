<?php
namespace GemLibrary\Database;

use PDO;

/**
 * To connect Database and provide basic PDO function.
 *
 * @param bool $isConnected
 */
class PdoConnection
{
    private bool $isConnected;
    private ?string $error;
    private ?int $affectedRows;
    private string|false $lastInsertedId;
    private ?\PDOStatement $stsment;
    private ?\PDO $db;
    private float $startExecutionTime;
    private float $endExecutionTime;
    private ?string $_query;

    public function __construct()
    {
        $this->startExecutionTime = microtime(true);
        $this->error = 'before initialize connect function';
        $this->affectedRows = null;
        $this->lastInsertedId = false;
        $this->isConnected = false;
        $this->db = null;
        $this->stsment = null;
        $this->_query = null;
        $this->connect();
    }

    public function __destruct()
    {
        $this->secure();
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

    public function connect(): bool
    {
        //$db_connection_info = DB_CONNECTIONS[$this->connectionName];
        //$dsn__db = $db_connection_info['type'].':host='.$db_connection_info['host'].';dbname='.$db_connection_info['database_name'].';charset=utf8mb4';
        $dsn__db = 'mysql:host='.$_ENV['DB_HOST'].';port='.$_ENV['DB_PORT'].';dbname='.$_ENV['DB_NAME'].';charset='.$_ENV['DB_CHARSET'];
        try {
            $options__db = [
                \PDO::ATTR_PERSISTENT => true,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ];
            $this->db = new \PDO($dsn__db, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $options__db);
            $this->isConnected = true;
            $this->error  = null;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            $this->endExecutionTime = microtime(true);
            return false;
        }

        return $this->isConnected;
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

    public function getQuery(): null|string
    {
        return $this->_query;
    }

    /**
     * @param string $query
     * convert sql query to PDO Statement trough PDO::prepare()
     * if connect to databse is failed, set error
     */
    public function query(string $query): void
    {
        $this->_query = $query;
        if ($this->db) {
            $this->stsment = $this->db->prepare($query);
        } else {
            $this->error = 'Database connection is null,please check your connection to Database';
        }
    }

    /**
     * @param mixed $value
     * this method automatically detect value Type and bind Parameter to value
     */
    public function bind(string $param, mixed $value): void
    {
        $type = match (true) {
            \is_int($value) => \PDO::PARAM_INT,
            null === $value => \PDO::PARAM_NULL,
            \is_bool($value) => \PDO::PARAM_BOOL,
            \is_string($value) => \PDO::PARAM_STR,
            default => \PDO::PARAM_STR,
        };
        $this->stsment?->bindValue($param, $value, $type);
    }

    /**
     * @set error
     * @set affectedRows
     */
    public function execute(): bool
    {
        if ($this->db && $this->stsment) {
            try {
                $this->stsment->execute();
                $this->affectedRows = $this->stsment->rowCount();
                $this->lastInsertedId = $this->db->lastInsertId();
                $this->endExecutionTime = microtime(true);

            } catch (\PDOException $e) {
                $this->endExecutionTime = microtime(true);
                $this->error = $e->getMessage();

            }
            if (!isset($this->error)) {
                $this->endExecutionTime = microtime(true);
                return true;
            }
        } else {
            $this->error = 'PDO statement is NULL';
            $this->endExecutionTime = microtime(true);
        }
        $this->secure();
        return false;
    }

    /**
     * @return null|int Query affected Rows
     *
     * @IMPORTANT this method return null in case of Execution Error
     * @IMPORTANT if Select Execution was successfull but no records found, return 0 ,NOT null
     */
    public function affectedRows(): int|null
    {
        return $this->affectedRows;
    }

    /**
     * @return false|string
     *                      in case of Execution Error , Will return false
     */
    public function lastInsertId(): string|false
    {
        return $this->lastInsertedId;
    }

    /**
     * close Database connection and make resource free.
     */
    public function secure(): void
    {
        $this->db = null;
        $this->isConnected = false;
    }


    /**
     * @Query Execution time in microsecond
     */
    public function getExecutionTime(): float
    {
        if (isset($this->endExecutionTime)) {
            return ($this->endExecutionTime - $this->startExecutionTime) * 1000;
        }
        $this->error = 'Query never Executed';

        return -1;
    }

    /**
     * @return false|array<mixed>
     */
    public function fetchAll(): array|false
    {
        if ($this->stsment) {
            return $this->stsment->fetchAll(\PDO::FETCH_ASSOC);
        }
        $this->error = 'PDO Statement is null,please check your connection name or table name';
        return false;
    }
    /**
     * @return false|array<mixed>
     */
    public function fetchAllObjects(): array|false
    {
        if ($this->stsment) {
            return $this->stsment->fetchAll(\PDO::FETCH_OBJ);
        } 
        $this->error = 'PDO Statement is null,please check your connection name or table name';
        return false;
    }

    public function fetchColumn(): int|false
    {
        if ($this->stsment) {
            try{
                return (int)$this->stsment->fetchColumn();
            }catch(\PDOException $e){
                $this->error = $e->getMessage();
                return false;
            }
        }
        $this->error = 'PDO Statement is null,please check your connection name or table name';
        return false;
    }
}
