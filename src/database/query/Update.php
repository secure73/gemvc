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

class Update  implements QueryBuilderInterface
{
    use WhereTrait;

    public ?int $result;

    /**
     * @var array<mixed>
     */
    public array $values = [];

    /**
     * @var array<mixed>
     */
    public array $arrayBindValues = [];

    private string $_table;

    private string $_query;

    /**
     * @var array<string>
     */
    private $columns = [];

    /**
     * @var array<string>
     */
    private array $whereConditions = [];
    
    /**
     * Store the last error message
     */
    private ?string $_lastError = null;
    
    /**
     * Reference to the query builder that created this update query
     */
    private ?QueryBuilder $queryBuilder = null;

    public function __construct(string $table)
    {
        $this->_table = $table;
    }

    public function __toString(): string
    {
        $this->_query = 'UPDATE ' . $this->_table . ' SET ' . implode(', ', $this->columns)
            . ([] === $this->whereConditions ? '' : ' WHERE ' . implode(' AND ', $this->whereConditions));

        return $this->_query;
    }

    public function set(string $column, mixed $value): self
    {
        $colToUpdate = ':' . $column . 'ToUpdate';
        $this->columns[] = "{$column} = {$colToUpdate}";
        $this->arrayBindValues[$colToUpdate] = $value;

        return $this;
    }

    /**
     * Set the query builder reference
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    public function run(): int|false
    {
        $pdoQuery = new PdoQuery();
        $query = $this->__toString();
        $result = $pdoQuery->updateQuery($query, $this->arrayBindValues);
        if(!$result) {
            $this->_lastError = $pdoQuery->getError();
        }
        
        // Register this query with the builder for error tracking
        if ($this->queryBuilder !== null) {
            $this->queryBuilder->setLastQuery($this);
        }
        
        return $result;
    }
    
    /**
     * Get the last error message if any
     */
    public function getError(): ?string
    {
        return $this->_lastError;
    }

}
