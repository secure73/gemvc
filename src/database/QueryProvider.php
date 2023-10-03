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

class QueryProvider extends DatabasePdoConnection
{
    private DatabasePdoConnection $connection;
    /**
     * @if null , use default connection in config.php
     * pass $connection name to parent and create PDO Connection to Execute Query
     */
    public function __construct(DatabasePdoConnection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection():DatabasePdoConnection
    {
        return $this->connection;
    }

    public function getError():string|null
    {
        return $this->connection->getError();
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function lastInsertId():string|false
    {
        return $this->connection->lastInsertId();
    }

    /**
     * @return null|int Returns query affected rows
     */
    public function affectedRows(): ?int
    {
        return $this->connection->affectedRows();
    }

    /**
     * @param string $insertQuery Sql insert query
     * @param array<string,mixed> $arrayBindKeyValue
     *
     * @return null|int
     * $query example: 'INSERT INTO users (name,email,password) VALUES (:name,:email,:password)'
     * arrayBindKeyValue Example [':name' => 'some new name' , ':email' => 'some@me.com , :password =>'si§d8x']
     * success : return last insertd id
     * you can call affectedRows() to get how many rows inserted
     * error: $this->getError();
     */
    public function insertQuery(string $insertQuery, array $arrayBindKeyValue = []): int|null
    {
        if($this->isConnected())
        {
            if ($this->executeQuery($insertQuery, $arrayBindKeyValue)) {
                return (int) $this->lastInsertId();
            }
            $this->secure();
        }

        return null;
    }

    /**
     * @param array<mixed> $arrayBindKeyValue
     *
     * @return null|array<mixed>
     *
     * @$query example: 'SELECT * FROM users WHERE email = :email'
     * @arrayBindKeyValue Example [':email' => 'some@me.com']
     */
    public function selectQuery(string $selectQuery, array $arrayBindKeyValue = []): array|null
    {
        $result = null;
        if($this->isConnected())
        {
            if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
                $result = $this->connection->fetchAll();
            }
            $this->secure();
        }
        return $result;
    }

    /**
     * @param array<mixed> $arrayBindKeyValue
     *
     * @$query example: 'SELECT count(*) FROM users WHERE name LIKE :name'
     *
     * @arrayBindKeyValue Example [':name' => 'someone']
     */
    public function countQuery(string $selectCountQuery, array $arrayBindKeyValue = []): int|false
    {
        $result = false;
        if($this->isConnected())
        {
            if ($this->executeQuery($selectCountQuery, $arrayBindKeyValue)) {
                $result = $this->connection->fetchColumn();
            }
            $this->secure();
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $arrayBindKeyValue
     *
     * @return null|int
     * $query example: 'UPDATE users SET name = :name , isActive = :isActive WHERE id = :id'
     * arrayBindKeyValue Example [':name' => 'some new name' , ':isActive' => true , :id => 32 ]
     * in success return positive number affected rows and in error null
     */
    public function updateQuery(string $updateQuery, array $arrayBindKeyValue = []): int|null
    {
        $result = null;
        if($this->isConnected())
        {
            if ($this->executeQuery($updateQuery, $arrayBindKeyValue)) {
                $result = $this->affectedRows();
            }
            $this->connection->secure();
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
     * @success return positive number affected rows and in error null
     */
    public function deleteQuery(string $deleteQuery, array $arrayBindKeyValue = []): int|null
    {
        $result = null;
        if($this->isConnected())
        {
            if ($this->executeQuery($deleteQuery, $arrayBindKeyValue)) {
                $result = $this->affectedRows();
            }
            $this->connection->secure();
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
            $this->connection->query($query);
            foreach ($arrayBind as $key => $value) {
                $this->connection->bind($key, $value);
            }
            return $this->connection->execute();
        }
        return false;
    }
}
