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
    /**
     * @if null , use default connection in config.php
     * pass $connection name to parent and create PDO Connection to Execute Query
     */
    public function __construct(?string $connectionName = null)
    {
        if (!$connectionName) {
            $connectionName = DEFAULT_CONNECTION_NAME;
        }
        parent::__construct($connectionName);
    }

    /**
     * @param array<string,mixed> $arrayBindKeyValue
     *
     * @return null|int
     *                  $query example: 'INSERT INTO users (name,email,password) VALUES (:name,:email,:password)'
     *                  arrayBindKeyValue Example [':name' => 'some new name' , ':email' => 'some@me.com , :password =>'si§d8x']
     *                  success : return last insertd id
     *                  you can call affectedRows() to get how many rows inserted
     *                  error: $this->error
     */
    public function insertQuery(string $insertQuery, array $arrayBindKeyValue = []): int|null
    {
        if ($this->executeQuery($insertQuery, $arrayBindKeyValue)) {
            return (int) $this->lastInsertId();
        }
        $this->secure();

        return null;
    }

    /**
     * @param array<mixed> $arrayBindKeyValue
     *
     * @return null|array<mixed>
     *
     * @$query example: 'SELECT * FROM users WHERE email = :email'
     *
     * @arrayBindKeyValue Example [':email' => 'some@me.com']
     */
    public function selectQuery(string $selectQuery, array $arrayBindKeyValue = []): array|null
    {
        $result = null;
        if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
            $result = $this->fetchAll();
        }
        $this->secure();

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
        if ($this->executeQuery($selectCountQuery, $arrayBindKeyValue)) {
            $result = $this->fetchColumn();
        }
        $this->secure();

        return $result;
    }

    /**
     * @param array<string,mixed> $arrayBindKeyValue
     *
     * @return null|int
     *                  $query example: 'UPDATE users SET name = :name , isActive = :isActive WHERE id = :id'
     *                  arrayBindKeyValue Example [':name' => 'some new name' , ':isActive' => true , :id => 32 ]
     *                  in success return positive number affected rows and in error null
     */
    public function updateQuery(string $updateQuery, array $arrayBindKeyValue = []): int|null
    {
        $result = null;
        if ($this->executeQuery($updateQuery, $arrayBindKeyValue)) {
            $result = $this->affectedRows();
        }
        $this->secure();

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
        if ($this->executeQuery($deleteQuery, $arrayBindKeyValue)) {
            $result = $this->affectedRows();
        }
        $this->secure();

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
        if ($this->connect()) {
            $this->query($query);
            foreach ($arrayBind as $key => $value) {
                $this->bind($key, $value);
            }

            return $this->execute();
        }

        return false;
    }
}
