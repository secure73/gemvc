<?php

namespace Gemvc\Database;

use Gemvc\Database\Query\Delete;
use Gemvc\Database\Query\Insert;
use Gemvc\Database\Query\Select;
use Gemvc\Database\Query\Update;

/**
 * Build and run Sql Queries without writing query-string
 */
class QueryBuilder
{
    public static function select(string ...$select): Select
    {
        return new Select($select);
    }

    /**
     * @param string $intoTableName
     */
    public static function insert(string $intoTableName): Insert
    {
        return new Insert($intoTableName);
    }

    /**
     * @param string $tableName
     */
    public static function update(string $tableName): Update
    {
        return new Update($tableName);
    }

    /**
     * @param string $tableName 
     * Delete from table
     */
    public static function delete(string $tableName): Delete
    {
        return new Delete($tableName);
    }
}
