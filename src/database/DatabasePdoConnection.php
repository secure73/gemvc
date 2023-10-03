<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gemvc\Database;

use PDO;

/**
 * To connect Database and provide basic PDO function.
 *
 * @param bool $isConnected
 */
class DatabasePdoConnection
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

    /**
     * @param string $connectionName
     * create new PDO Database connection Instance and create connection
     * to assert connection sucess ,$instance->getError();
     */
    public function __construct(string $connection_string,string $db_username, string $db_password , array $options = null)
    {
        $this->startExecutionTime = microtime(true);
        $this->error = null;
        $this->affectedRows = null;
        $this->lastInsertedId = false;
        $this->isConnected = false;
        $this->db = null;
        $this->stsment = null;
        $this->_query = null;
        $this->connect($connection_string,$db_username,$db_password,$options);
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function connect(string $connection_string , string $db_username , string $db_password , array $options = null): bool
    {
        //$db_connection_info = DB_CONNECTIONS[$this->connectionName];
        //$dsn__db = $db_connection_info['type'].':host='.$db_connection_info['host'].';dbname='.$db_connection_info['database_name'].';charset=utf8mb4';

        try {
            $options__db = [
                \PDO::ATTR_PERSISTENT => true,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ];
            if (is_array($options))
            {
                $options__db = $options;
            }
            $this->db = new \PDO($connection_string, $db_username, $db_password, $options__db);
            $this->isConnected = true;
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
            $this->endExecutionTime = microtime(true);
        }

        return $this->isConnected;
    }

    /**
     * If you want To Sect some Custome Error rather than Default SQL Exceptopn for special cases.
     */
    public function setError(string $message): void
    {
        $this->error = $message;
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
        $this->stsment = null;
        $this->db = null;
        $this->isConnected = false;
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
     * @return null|array<mixed>
     */
    public function fetchAll(): array|null
    {
        $result = null;
        if ($this->stsment) {
            $result = $this->stsment->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->error = 'PDO Statement is null,please check your connection name or table name';
        }

        return $result;
    }
    /**
     * @return null|array<mixed>
     */
    public function fetchAllObjects():array|null
    {
        $result = null;
        if ($this->stsment) {
            $result = $this->stsment->fetchAll(\PDO::FETCH_OBJ);
        } else {
            $this->error = 'PDO Statement is null,please check your connection name or table name';
        }

        return $result;
    }

    public function fetchColumn(): int|false
    {
        $result = false;
        if ($this->stsment) {
            $result = $this->stsment->fetchColumn();
            if (false !== $result) {
                $result = (int) $result;
            }
        } else {
            $this->error = 'PDO Statement is null,please check your connection name or table name';
        }

        return $result;
    }
}
