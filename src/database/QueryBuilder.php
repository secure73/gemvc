<?php

namespace GemLibrary\Database;

use GemLibrary\Database\Query\Delete;
use GemLibrary\Database\Query\Insert;
use GemLibrary\Database\Query\Select;
use GemLibrary\Database\Query\Update;

class QueryBuilder
{
    public static function select(string ...$select): Select
    {
        return new Select($select);
    }

    /**
     * @param string $intoTable
     */
    public static function insert(string $intoTable): Insert
    {
        return new Insert($intoTable);
    }

    /**
     * @param string $table
     */
    public static function update(string $table): Update
    {
        return new Update($table);
    }

    /**
     * @param string $table     
     * Delete from table
     */
    public static function delete(string $table): Delete
    {
        return new Delete($table);
    }
}
