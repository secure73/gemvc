<?php

namespace Gemvc\Database;
class PdoQuery extends QueryExecuter
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param string $insertQuery Sql insert query
     * @param array<string, string|int|float|bool|null> $arrayBindKeyValue
     * @return false|int
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
     * @param array<string,mixed> $arrayBindKeyValue
     * @return int|null
     * $query example: 'UPDATE users SET name = :name , isActive = :isActive WHERE id = :id'
     * arrayBindKeyValue Example [':name' => 'some new name' , ':isActive' => true , :id => 32 ]
     * Returns the number of affected rows, 0 if no changes were made, or null on error
     */
    public function updateQuery(string $updateQuery, array $arrayBindKeyValue = []): ?int
    {
        if (!$this->isConnected()) {
            $this->setError('Database connection not established');
            return null;
        }

        try {
            if ($this->executeQuery($updateQuery, $arrayBindKeyValue)) {
                $affectedRows = $this->affectedRows();
                if ($affectedRows === 0) {
                    $this->setError('No rows were updated. The data might be unchanged.');
                }
                return $affectedRows;
            }
        } catch (\PDOException $e) {
            // Check for specific error codes that might indicate "no changes"
            if ($e->getCode() == '00000' && strpos($e->getMessage(), 'no affected rows') !== false) {
                $this->setError('No changes were made. The data might be identical.');
                return 0;
            }
            
            // For other errors, set the error message
            $this->setError('Database error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param array<mixed> $arrayBind
     * @return bool
     * @throws \PDOException
     */
    private function executeQuery(string $query, array $arrayBind): bool
    {
        if (!$this->isConnected()) {
            throw new \PDOException('Database connection not established');
        }

        $this->query($query);
        foreach ($arrayBind as $key => $value) {
            $this->bind($key, $value);
        }
        return $this->execute();
    }

    /**
     * @param             array<string,mixed> $arrayBindKeyValue
     * @query             example: 'DELETE users SET name = :name , isActive = :isActive WHERE id = :id'
     * @arrayBindKeyValue example [':id' => 32 ]
     * @success           return positive number affected rows and in error false
     */
    public function deleteQuery(string $deleteQuery, array $arrayBindKeyValue = []): int|null|false
    {
        if ($this->isConnected()) {
            if ($this->executeQuery($deleteQuery, $arrayBindKeyValue)) {
                return $this->affectedRows();
            }
        }
        return false;
    }


    /**
     * @param             array<mixed> $arrayBindKeyValue
     * @return            false|array<mixed>
     * @$query            example: 'SELECT * FROM users WHERE email = :email'
     * @arrayBindKeyValue Example [':email' => 'some@me.com']
     */
    public function selectQueryObjects(string $selectQuery, array $arrayBindKeyValue = []): array|false
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
      * @param             array<mixed> $arrayBindKeyValue
      * @return            false|array<mixed>
      * @$query            example: 'SELECT * FROM users WHERE email = :email'
      * @arrayBindKeyValue Example [':email' => 'some@me.com']
      */
    public function selectQuery(string $selectQuery, array $arrayBindKeyValue = []): array|false
    {
        if (!$this->isConnected()) {
            return false;
        }
        if ($this->executeQuery($selectQuery, $arrayBindKeyValue)) {
            return $this->fetchAll();
        }
        return false;
    }

    /**
     * @param             string       $selectCountQuery
     * @param             array<mixed> $arrayBindKeyValue
     * @return            int|false
     * @$query            example: 'SELECT COUNT(*) FROM users WHERE name LIKE :name'
     * @arrayBindKeyValue Example [':name' => 'someone']
     */
    public function selectCountQuery(string $selectCountQuery, array $arrayBindKeyValue = []): int|false
    {
        if (!$this->isConnected()) {
            return false;
        }
        if ($this->executeQuery($selectCountQuery, $arrayBindKeyValue)) {
            $result = $this->fetchColumn();
            if ($result !== false && is_numeric($result)) {
                return (int) $result;
            }
        }
        return false;
    }
}
