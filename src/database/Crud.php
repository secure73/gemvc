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

/**
 * provide Predefeined commonly used sql Queries for PHP objects trough its function.
 */
class Crud extends QueryProvider
{
    protected ?string $table;
    public int $id;

    public function __construct(string $tableName, ?string $connectionName = null)
    {
        $connectionName ?: DEFAULT_CONNECTION_NAME;
        $this->table = $tableName;
        parent::__construct($connectionName);
    }

    public function getById(int $id): void
    {
        $row = $this->selectQuery("SELECT * FROM {$this->table} WHERE id = :id", [':id' => $id]);
        if (null !== $row && isset($row[0]) && \is_array($row[0])) {
            $this->fetchRowToThis($row[0]);
        } else {
            $this->setError('Object with given id dose not Existed');
        }
    }

    /**
     * @return null|string
     *                     current in use table
     */
    public function getTable(): string|null
    {
        return $this->table;
    }

    /**
     * @insert current instance into Database
     *
     * @will return lastInsertedId
     *
     * @you can call affectedRows() and it shall be 1
     *
     * @error: $this->getError();
     */
    public function insert(): int|null
    {
        $columns = '';
        $params = '';
        $arrayBind = [];
        $query = "INSERT INTO {$this->table} ";
        // @phpstan-ignore-next-line
        foreach ($this as $key => $value) {
            if ('table' !== $key) {
                $columns .= $key.',';
                $params .= ':'.$key.',';
                $arrayBind[':'.$key] = $value;
            }
        }
        $columns = rtrim($columns, ',');
        $params = rtrim($params, ',');

        $query .= " ({$columns}) values ({$params})";
        return $this->insertQuery($query, $arrayBind);
    }

    /**
     * @return null|int
     *                  update current Object
     */
    public function update(): int|null
    {
        $arrayBind = [];
        $id = null;
        $table = $this->table;
        if (isset($this->id)) {
            $id = $this->id;
        }
        if ($id > 0) {
            unset($this->table);
            $query = "UPDATE  $table SET ";
            // @phpstan-ignore-next-line
            foreach ($this as $key => $value) {
                $query .= " {$key} = :{$key},";
                $arrayBind[":{$key}"] = $value;
            }
            $this->table = $table;
            $this->id = $id;
            $query = rtrim($query, ',');
            $query .= ' WHERE id = :id';
            $arrayBind[':id'] = $id;
            return $this->updateQuery($query, $arrayBind);
        }

        return null;
    }

    public function activate():int|null
    {
        $query = "UPDATE $this->table SET isActive = 1 WHERE id = :id";
        $arrayBind[':id'] = $this->id;
        return $this->updateQuery($query, $arrayBind);
    }

    public function deactivate():int|null
    {
        $query = "UPDATE $this->table SET isActive = 0 WHERE id = :id";
        $arrayBind[':id'] = $this->id;
        return $this->updateQuery($query, $arrayBind);
    }

    /**
     * @param array<mixed> $bindValues
     *
     * @return null|array<$this>
     *
     * @$query example: 'SELECT * FROM users WHERE email = :email'
     *
     * @bindValues Example [':email' => 'some@me.com']
     *
     * @OR
     *
     * @bindValues = [] , $bindValues[':email'] = 'some@me.com';
     */
    public function select(string $query, array $bindValues): array|null
    {
        $queryResult = $this->selectQuery($query, $bindValues);
        if (\is_array($queryResult)) {
            $objects_result = [];
            foreach ($queryResult as $row) {
                $instance = new $this();
                // @phpstan-ignore-next-line
                $instance->fetchRowToThis($row);
                $objects_result[] = $instance;
            }

            return $objects_result;
        }

        return null;
    }

    /**
     * @return null|array<$this>
     */
    public function columnSelect(
        ?string $firstColumn = null,
        ?\SqlEnumCondition $firstCondition = null,
        mixed $firstValue = null,
        ?string $secondColumn = null,
        ?\SqlEnumCondition $secondCondition = null,
        mixed $secondValue = null,
        ?string $orderBy = null,
        ?string $ASC_DES = null,
        ?int $limit_count = null,
        ?int $limit_offset = null,
        ?bool $isDel = null,
        ?bool $deactives = null,
        ?bool $actives = null

    ): null|array {
        $limit = '';
        $arrayBindValue = [];

        $isDel ? ' AND deleted_at IS NOT NULL ' : '';
        $actives ? ' AND isActive = 1 ' : '';
        
        $deactives ? ' AND isActive IS NOT 1 ' : '';
        if ($orderBy) {
            $orderBy = " ORDER BY {$orderBy} {$ASC_DES}";
        }
        if ($limit_count) {
            $limit = " LIMIT {$limit_count}";
            if ($limit_offset) {
                $limit = " LIMIT {$limit_offset} , {$limit_count}";
            }
        }

        $query = "SELECT * FROM {$this->table} ";
        $firstColumnQuery = null;
        $secondColumnQuery = null;

        if(null !== $firstColumn && null !== $firstCondition)
        {
            $firstValue = (' LIKE ' === (string) $firstCondition->value) ? '%'.$firstValue.'%' : $firstValue;
            $firstColumnQuery = " {$firstColumn} {$firstCondition->value} :{$firstColumn}";
            $arrayBindValue[':'.$firstColumn] = $firstValue;
        }

        if (null !== $secondColumn && null !== $secondCondition) {
            $secondColumnQuery = " AND {$secondColumn} {$secondCondition->value} :{$secondColumn}";
            $secondValue = (' LIKE ' === $secondCondition->value) ? '%'.$secondValue.'%' : $secondValue;
            $arrayBindValue[':'.$secondColumn] = $secondValue;
        }
        $query .= "WHERE 1 {$firstColumnQuery} {$secondColumnQuery} {$isDel} {$actives} {$deactives} {$orderBy} {$limit}";
        $query = trim($query);
        //echo $query;
        return $this->select($query, $arrayBindValue);
    }




    /**
     * @param array<int> $ids
     *
     * @return null|array<$this>
     */
    public function ids(array $ids): array|null
    {
        $stringIds = '';
        foreach ($ids as $id) {
            $stringIds .= $id.',';
        }
        $stringIds = rtrim($stringIds, ',');
        $query = "SELECT * FROM {$this->table} WHERE id IN ({$stringIds})";

        return $this->select($query, []);
    }

    public function id(int $id): void
    {
        $row = $this->select("SELECT * FROM {$this->table} WHERE id = :id", [':id' => $id]);
        if (null !== $row && \is_array($row)) {
            foreach ($row as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return null|array<$this>
     */
    public function firstRows(
        int $countRows,
        string $whereColumn,
        \SqlEnumCondition $whereCondition,
        mixed $whereValue,
        ?string $orderByColumnName = null
    ): null|array {
        $arrayBindValue = [];
        $where = " WHERE {$whereColumn} ".$whereCondition->value." :{$whereColumn}";
        $arrayBindValue[':'.$whereColumn] = $whereValue;
        $query = ($orderByColumnName) ? "SELECT * FROM {$this->table} ORDER BY {$orderByColumnName} {$where} LIMIT {$countRows}" :
                                            "SELECT * FROM {$this->table} {$where} LIMIT {$countRows}";

        return $this->select($query, $arrayBindValue);
    }

    /**
     * @return null|$this
     */
    public function first(string $whereColumn, \SqlEnumCondition $whereCondition, int|string|bool $whereValue = null): null|object
    {
        $result = $this->firstRows(1, $whereColumn, $whereCondition, $whereValue);
        if (\is_array($result) && isset($result[0])) {
            return $result[0];
        }

        return null;
    }

    /**
     * @return null|array<$this>
     */
    public function lastRows(int $countRows, string $orderByColumnName, ?string $whereColumn = null, ?\SqlEnumCondition $whereCondition = null, int|string|bool $whereValue = null): null|array
    {
        // TODO
        $arrayBindValue = [];
        $where = '';
        if (null !== $whereColumn && null !== $whereCondition) {
            $where = " WHERE {$whereColumn} ".$whereCondition->value;
        }
        if (null !== $whereValue) {
            $where .= " :{$whereValue}";
            $arrayBindValue[':'.$whereValue] = $whereValue;
        }

        $query = "SELECT * FROM {$this->table} ORDER BY {$orderByColumnName} DESC {$where} LIMIT {$countRows}";

        return $this->select($query, $arrayBindValue);
    }

    /**
     * @param array<mixed> $bindValues
     *
     * @return null|array<$this>
     *
     * @ $query example: SELECT * FROM users WHERE name LIKE :name AND isDel IS NULL
     * @ $bindVaues = ['name'=> '%'.John.'%']
     * $user = new User();
     * arrrayUsers = $user->selectPagination($query, $bindValues , 15 , 2);
     */
    public function selectPagination(string $selectQuery, array $bindValues, int $perPage, int $page): array|null
    {
        $selectQuery .= " LIMIT {$page} , {$perPage}";

        return $this->select($selectQuery, $bindValues);
    }

    /**
     * @set a specific column to null based on condition whereColumn = $whereValue
     *
     * @exampel $this->setNull('deleted_at,'id',$this->id);
     *
     * @explain:  set deleted_at to null where id = $this->id
     */
    public function setNull(string $columnNameSetToNull, string $whereColumn, mixed $whereValue): int|null
    {
        $query = "UPDATE {$this->table}  SET  {$columnNameSetToNull} = NULL  WHERE  {$whereColumn}  = :whereValue";

        return $this->updateQuery($query, [':whereValue' => $whereValue]);
    }

    /**
     * @set a specific column to time now based on condition whereColumn = $whereValue
     *
     * @exampel $order->setTimeNow('paid_at','id',$this->id);
     *
     * @explain:  set paid_at  to 18-08-2022 12:45:13 where id = $this->id
     */
    public function setTimeNow(string $columnNameSetToNowTomeStamp, string $whereColumn, mixed $whereValue): int|null
    {
        $query = "UPDATE {$this->table}  SET  {$columnNameSetToNowTomeStamp} = NOW()  WHERE  {$whereColumn}  = :whereValue";

        return $this->updateQuery($query, [':whereValue' => $whereValue]);
    }

    /**
     * @ in case of success return 1
     * @Attention:  remove Object compleetly from Database
     */
    public function delete(): int|null
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        if (isset($this->id) && $this->id > 0) {
            return $this->deleteQuery($query, [':id' => $this->id]);
        }
        $this->setError('Object id is not set or it is less than 1');

        return null;
    }

    /**
     * NOTE:  remove Object compleetly from Database.
     *
     * @ in case of success return count removed items
     * @Attention:  remove Object compleetly from Database
     */
    public function RemoveConditional(string $whereColumn, mixed $whereValue, ?string $secondWhereColumn = null, mixed $secondWhereValue = null): int|null
    {
        $query = "DELETE FROM {$this->table} WHERE {$whereColumn} = :{$whereColumn}";
        if ($secondWhereColumn) {
            $query .= " AND {$secondWhereColumn} = :{$secondWhereColumn}";
        }
        $arrayBind[':'.$whereColumn] = $whereValue;
        if ($secondWhereColumn) {
            $arrayBind[':'.$secondWhereColumn] = $secondWhereValue;
        }

        return $this->deleteQuery($query, $arrayBind);
    }

    public function safeDelete(int $id = null): int|null
    {
        if (!$id) {
            $id = $this->id;
        }
        $table = $this->getTable();
        $query = "UPDATE {$table} SET deleted_at = NOW() AND isActive = 0 WHERE id = :id";

        return $this->updateQuery($query, [':id' => $id]);
    }

    public function restore(int $id = null): int|null
    {
        if (!$id) {
            $id = $this->id;
        }
        $query = "UPDATE {$this->table} SET deleted_at = NULL WHERE id = :id";

        return $this->updateQuery($query, [':id' => $id]);
    }

    public function restoreActivate(int $id = null): int|null
    {
        if (!$id) {
            $id = $this->id;
        }
        $query = "UPDATE {$this->table} SET deleted_at = NULL WHERE id = :id AND isActive = :isActive";

        return $this->updateQuery($query, [':id' => $id,':isActive'=>1]);
    }

    /**
     * @return array<mixed>|null
     */
    public function list():null|array
    {
        $arrayBindValue =[];
        $query = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
        return $this->select($query, $arrayBindValue);
    }

    /**
     * @param array<mixed> $row
     */
    private function fetchRowToThis(array $row): void
    {
        foreach ($row as $key => $value) {
            $this->$key = $value;
        }
    }
}
