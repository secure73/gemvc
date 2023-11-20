<?php

namespace GemLibrary\Database;

use GemLibrary\Database\PdoConnection;

class PdoQuery extends PdoConnection
{
    private ?int $limit;
    private ?int $offset;
    private ?string $orderBy;
    private bool $DESC = true;
 
    /**
     * @if null , use default connection in config.php
     * pass $connection name to parent and create PDO Connection to Execute Query
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $orderBy
     * @param bool $DESC
     */
    public function setOrderBy(string $orderBy, bool $DESC = true):void
    {
        $this->orderBy = $orderBy;
        $this->DESC = $DESC;
    }

    /**
     * @param int $limit
     * @param int $offset
     */
    public function setLimit(int $limit, int $offset = 0):void
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }


    /**
     * @param string $insertQuery Sql insert query
     * @param array<string,mixed> $arrayBindKeyValue
     *
     * @return false|int
     * $query example: 'INSERT INTO users (name,email,password) VALUES (:name,:email,:password)'
     * arrayBindKeyValue Example [':name' => 'some new name' , ':email' => 'some@me.com , :password =>'si§d8x']
     * success : return last insertd id
     * you can call affectedRows() to get how many rows inserted
     * error: $this->getError();
     */
    public function insertQuery(string $insertQuery, array $arrayBindKeyValue = []): int|false
    {
        if ($this->isConnected()) {
            if ($this->executeQuery($insertQuery, $arrayBindKeyValue)) {
                return (int) $this->lastInsertId();
            }
        }
        return false;
    }

    /**
     * @param array<mixed> $arrayBindKeyValue
     *
     * @return false|array<mixed>
     *
     * @$query example: 'SELECT * FROM users WHERE email = :email'
     * @arrayBindKeyValue Example [':email' => 'some@me.com']
     */
    public function selectQuery(string $selectQuery, array $arrayBindKeyValue = []): array|false
    {
        $result = false;
        if($this->orderBy){
            $selectQuery .= " ORDER BY {$this->orderBy} ";
            if($this->DESC){
                $selectQuery .= " DESC ";
            }
            else{
                $selectQuery .= " ASC ";
            }
        }
        if($this->limit){
            $selectQuery .= " LIMIT {$this->limit} ";
            if($this->offset){
                $selectQuery .= " OFFSET {$this->offset} ";
            }
        }

        if ($this->isConnected()) {
            if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
                $result = $this->fetchAll();
                if($result !== null ){

                }
            }
        }
        return $result;
    }

     /**
     * @param array<mixed> $arrayBindKeyValue
     * @return false|array<mixed>
     *
     * @$query example: 'SELECT * FROM users WHERE email = :email'
     * @arrayBindKeyValue Example [':email' => 'some@me.com']
     */
    public function selectQueryObjets(string $selectQuery, array $arrayBindKeyValue = []): array|false
    {
        $result = false;
        if ($this->isConnected()) {
            if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
                return $this->fetchAllObjects();
            }
        }
        return $result;
    }

    /**
     * @param array<mixed> $arrayBindKeyValue
     * @return int|false
     *
     * @$query example: 'SELECT count(*) FROM users WHERE name LIKE :name'
     *
     * @arrayBindKeyValue Example [':name' => 'someone']
     */
    public function countQuery(string $selectCountQuery, array $arrayBindKeyValue = []): int|false
    {
        $result = false;
        if ($this->isConnected()) {
            if ($this->executeQuery($selectCountQuery, $arrayBindKeyValue)) {
                $result = $this->fetchColumn();
            }
        }
        return $result;
    }

    /**
     * @param array<string,mixed> $arrayBindKeyValue
     *
     * @return false|int
     * $query example: 'UPDATE users SET name = :name , isActive = :isActive WHERE id = :id'
     * arrayBindKeyValue Example [':name' => 'some new name' , ':isActive' => true , :id => 32 ]
     * in success return positive number affected rows and in error false
     */
    public function updateQuery(string $updateQuery, array $arrayBindKeyValue = []): int|false
    {
        $result = false;
        if ($this->isConnected()) {
            if ($this->executeQuery($updateQuery, $arrayBindKeyValue)) {
                $result = $this->affectedRows();
            }
        }
        return $result;
    }

    /**
     * @param array<string,mixed> $arrayBindKeyValue
     *
     * @query example: 'DELETE users SET name = :name , isActive = :isActive WHERE id = :id'
     *
     * @arrayBindKeyValue example [':id' => 32 ]
     *
     * @success return positive number affected rows and in error false
     */
    public function deleteQuery(string $deleteQuery, array $arrayBindKeyValue = []): int|false
    {
        $result = false;
        if ($this->isConnected()) {
            if ($this->executeQuery($deleteQuery, $arrayBindKeyValue)) {
                $result = $this->affectedRows();
            }
        }

        return $result;
    }

    /**
     * @param array<mixed> $arrayBind
     *
     * @success set this->affectedRows
     *
     * @error set this->error and return false
     */
    private function executeQuery(string $query, array $arrayBind): bool
    {
        if ($this->isConnected()) {
            $this->query($query);
            foreach ($arrayBind as $key => $value) {
                $this->bind($key, $value);
            }
            return $this->execute();
        }
        return false;
    }
}
