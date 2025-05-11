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
    /**
     * Stores the last executed query object for error retrieval
     */
    private ?QueryBuilderInterface $lastQuery = null;

    public function select(string ...$select): Select
    {
        $query = new Select($select);
        $query->setQueryBuilder($this);
        return $query;
    }

    /**
     * @param string $intoTableName
     */
    public function insert(string $intoTableName): Insert
    {
        $query = new Insert($intoTableName);
        $query->setQueryBuilder($this);
        return $query;
    }

    /**
     * @param string $tableName
     */
    public function update(string $tableName): Update
    {
        $query = new Update($tableName);
        $query->setQueryBuilder($this);
        return $query;
    }

    /**
     * @param string $tableName 
     * Delete from table
     */
    public function delete(string $tableName): Delete
    {
        $query = new Delete($tableName);
        $query->setQueryBuilder($this);
        return $query;
    }
    
    /**
     * Get the error from the last executed query
     * 
     * @return string|null The error message or null if no error occurred
     */
    public function getError(): ?string
    {
        return $this->lastQuery?->getError();
    }
    
    /**
     * Set the last executed query object
     * This should be called by the query objects after execution
     * 
     * @param QueryBuilderInterface $query The query object that was executed
     */
    public function setLastQuery(QueryBuilderInterface $query): void
    {
        $this->lastQuery = $query;
    }
}
