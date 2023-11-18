<?php

namespace GemLibrary\Database;

use GemLibrary\Database\PdoQuery;
use GemLibrary\Database\Query\Delete;
use GemLibrary\Database\Query\Insert;
use GemLibrary\Database\Query\Select;
use GemLibrary\Database\Query\Update;

class QueryBuilder extends PdoQuery
{
    public function __construct()
    {
        parent::__construct();
    }

    public function select(string ...$select): Select
    {
        return new Select($select);
    }

    /**
     * @param string $intoTable
     */
    public function insert(string $intoTable): Insert
    {
        return new Insert($intoTable);
    }

    /**
     * @param string $table
     */
    public function update(string $table): Update
    {
        return new Update($table);
    }

    /**
     * @param string $table     
     * Delete from table
     */
    public  function delete(string $table): Delete
    {
        return new Delete($table);
    }
}
