<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

 namespace Gemvc\Database\Query;

use Gemvc\Database\PdoQuery;
use Gemvc\Database\QueryBuilderInterface;
use Gemvc\Database\QueryBuilder;

class Insert implements QueryBuilderInterface
{
    /**
     * @var null|int
     */
    public $result;

    private string $_table;

    private string $_query;

    /**
     * @var array<string>
     */
    private $columns = [];

    /**
     * @var array<string>
     */
    private $binds = [];

    /**
     * @var array<mixed>
     */
    private $values = [];

    /**
     * @var array<mixed>
     */
    private $keyValue = [];

    private ?string $_lastError = null;
    
    /**
     * Reference to the query builder that created this insert query
     */
    private ?QueryBuilder $queryBuilder = null;

    public function __construct(string $table)
    {
        $this->_table = $table;
        $this->_query = '';
    }

    /**
     * Set the query builder reference
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    public function __toString(): string
    {
        $this->_query = 'INSERT INTO ' . $this->_table
            . ' (' . implode(', ', $this->columns) . ') VALUES (' . implode(', ', $this->binds) . ')';

        return $this->_query;
    }

    public function columns(string ...$columns): self
    {
        $this->columns = $columns;
        foreach ($columns as $column) {
            $this->binds[] = ":{$column}";
        }

        return $this;
    }

    public function values(mixed ...$values): self
    {
        foreach ($values as $arg) {
            $this->values[] = $arg;
        }
        if (\count($this->binds) === \count($this->values)) {
            foreach ($this->binds as $key => $item) {
                $this->keyValue[$item] = $this->values[$key];
            }
        }

        return $this;
    }

    public function run():int|false
    {
        $pdoQuery = new PdoQuery();

        $query = $this->__toString();
        $result = $pdoQuery->insertQuery($query, $this->keyValue);
        if(!$result) {
            $this->_lastError = $pdoQuery->getError();
        }
        
        // Register this query with the builder for error tracking
        if ($this->queryBuilder !== null) {
            $this->queryBuilder->setLastQuery($this);
        }
        
        return $result;
    }

    public function getError(): ?string
    {
        return $this->_lastError;
    }
    
}
