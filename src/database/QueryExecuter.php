<?php
namespace GemLibrary\Database;
use GemLibrary\Database\PdoConnection;
use PDO;

class QueryExecuter {
    private ?string $error;
    private ?int $affectedRows;
    private string|false $lastInsertedId;
    private ?\PDOStatement $stsment;
    private float $startExecutionTime;
    private float $endExecutionTime;
    private ?string $_query;
    private PDO|null $db;
    private bool $isConnected = false;
    public function __construct()
    {
        $connection = new PdoConnection();
        $this->db = $connection->connect();
        $this->error = $connection->getError();
        $this->isConnected = $connection->isConnected();
        $this->startExecutionTime = microtime(true);
    }

    public function getQuery(): null|string
    {
        return $this->_query;
    }

    public function isConnected():bool
    {
        return $this->isConnected;
    }

    /**
     * @param string $query
     * convert sql query to PDO Statement trough PDO::prepare()
     * if connect to databse is failed, set error
     */
    public function query(string $query): void
    {
        $this->_query = $query;
        if ($this->isConnected && $this->db) {
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

    public function getError():?string
    {
        return $this->error;
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
    }


    /**
     * @Query Execution time in microsecond
     */
    public function getExecutionTime(): float
    {
        if (isset($this->endExecutionTime)) {
            return ($this->endExecutionTime - $this->startExecutionTime) * 1000;
        }
        $this->error = 'Query never Executed successfuly,please check your query and try again';

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
     * return stdClass object
     */
    public function fetchAllObjects(): array|false
    {
        if ($this->stsment) {
            return $this->stsment->fetchAll(\PDO::FETCH_OBJ);
        } 
        $this->error = 'PDO Statement is null,please check your connection name or table name';
        return false;
    }

    /**
     * @return false|array<object>
     * @param string $targetClassName class name to convert result into it
     */
    public function fetchAllClass(string $targetClassName): array|false
    {
        if ($this->stsment) {
            try{
                return $this->stsment->fetchAll(\PDO::FETCH_CLASS,$targetClassName);
            }catch(\PDOException $e){
                $this->error = $e->getMessage();
                return false;
            }
        } 
        $this->error = 'PDO Statement is null,please check your connection name or table name';
        return false;
    }

    public function fetchColumn(): mixed
    {
        if ($this->stsment) {
            try{
                return $this->stsment->fetchColumn();
            }catch(\PDOException $e){
                $this->error = $e->getMessage();
                return false;
            }
        }
        $this->error = 'PDO Statement is null,please check your connection name or table name';
        return false;
    }
}
