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

class Select implements QueryBuilderInterface
{
    use LimitTrait;
    use WhereTrait;

    public mixed $result = null;

    public ?string $json = null;

    /**
     * @var array<object>
     */
    public array $object = [];

    public string $query = "";

    /**
     * @var array<mixed>
     */
    public array $arrayBindValues = [];

    /**
     * Store the last error message
     */
    private ?string $_lastError = null;

    /**
     * Reference to the query builder that created this select query
     */
    private ?QueryBuilder $queryBuilder = null;

    /**
     * @var array<mixed>
     */
    private array $fields = [];

    /**
     * @var array<string>
     */
    private array $whereConditions = [];

    /**
     * @var array<string>
     */
    private array $order = [];

    /**
     * @var array<string>
     */
    private array $from = [];

    /**
     * @var array<string>
     */
    private array $innerJoin = [];

    /**
     * @var array<string>
     */
    private array $leftJoin = [];

    /** @var int|null */
    private $limit = null;
    /** @var int|null */
    private $offset = null;

    /**
     * Generate the LIMIT clause with modern SQL syntax
     * This method is used by the LimitTrait
     * 
     * @return string The LIMIT clause string
     */
    private function limitMaker(): string
    {
        $limitQuery = '';
        
        // Handle LIMIT clause
        if (isset($this->limit) && $this->limit >= 0) {
            $limitQuery = ' LIMIT ' . $this->limit;
            
            // Add OFFSET if specified and valid
            if (isset($this->offset) && $this->offset > 0) {
                $limitQuery .= ' OFFSET ' . $this->offset;
            }
        }
        // Handle OFFSET without LIMIT (supported by some databases)
        elseif (isset($this->offset) && $this->offset > 0) {
            // For databases that support OFFSET without LIMIT, use a large number
            // This is more compatible across different database systems
            $limitQuery = ' LIMIT 18446744073709551615 OFFSET ' . $this->offset;
        }
        
        return $limitQuery;
    }

    /**
     * Set limit value (used by LimitTrait)
     * @param int|null $limit
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Set offset value (used by LimitTrait)
     * @param int|null $offset
     */
    public function setOffset(?int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * @param array<mixed> $select
     */
    public function __construct(array $select)
    {
        $this->fields = $select;
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
        $this->query = $this->selectMaker() . implode(', ', $this->from)
            . ([] === $this->leftJoin ? '' : ' LEFT JOIN ' . implode(' LEFT JOIN ', $this->leftJoin))
            . ([] === $this->innerJoin ? '' : ' INNER JOIN ' . implode(' INNER JOIN ', $this->innerJoin))
            . ([] === $this->whereConditions ? '' : ' WHERE ' . implode(' AND ', $this->whereConditions))
            . ([] === $this->order ? '' : ' ORDER BY ' . implode(', ', $this->order))
            . $this->limitMaker();
        // echo $this->query;
        return $this->query;
    }

    public function select(string ...$select): self
    {
        foreach ($select as $arg) {
            $this->fields[] = $arg;
        }

        return $this;
    }

    public function from(string $table, ?string $alias = null): self
    {
        $this->from[] = null === $alias ? $table : "{$table} AS {$alias}";

        return $this;
    }

    public function orderBy(string $columnName, ?bool $descending = null): self
    {
        if ($descending) {
            $this->order[] = $columnName . ' ' . \SqlEnumCondition::Descending->value . ' ';
        } else {
            $this->order[] = $columnName;
        }

        return $this;
    }

    public function innerJoin(string ...$join): self
    {
        $this->leftJoin = [];
        foreach ($join as $arg) {
            $this->innerJoin[] = $arg;
        }

        return $this;
    }

    public function leftJoin(string ...$join): self
    {
        $this->innerJoin = [];
        foreach ($join as $arg) {
            $this->leftJoin[] = $arg;
        }

        return $this;
    }

    /**
     * Run the select query and return the results
     * Following our unified return pattern: result|null
     * 
     * @return array<mixed>|null Array of results on success, null on error
     */
    public function run(): array|null
    {
        // Use the shared PdoQuery instance from QueryBuilder if available
        $pdoQuery = $this->queryBuilder ? $this->queryBuilder->getPdoQuery() : new PdoQuery();
        
        $query = $this->__toString();
        $result = $pdoQuery->selectQuery($query, $this->arrayBindValues);
        
        if ($result === null) {
            $this->_lastError = $pdoQuery->getError();
            // Register this query with the builder for error tracking
            if ($this->queryBuilder !== null) {
                $this->queryBuilder->setLastQuery($this);
            }
            return null;
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

    /**
     * Execute query and return results as JSON string
     * 
     * @return string|null JSON string on success, null on error
     */
    public function json(): string|null
    {
        $result = $this->run();
        
        if ($result === null) {
            return null;
        }
        
        $jsonResult = json_encode($result);
        if ($jsonResult === false) {
            $this->_lastError = "Failed to encode results as JSON";
            return null;
        }
        
        return $jsonResult;
    }

    /**
     * @param  PdoQuery $classTable
     * @return array<mixed>
     */
    public function object(PdoQuery $classTable): array
    {
        $query = $this->__toString();
        $result = $classTable->selectQuery($query, $this->arrayBindValues);
        if ($result === null) {
            $this->_lastError = $classTable->getError();
            return [];
        }
        foreach ($result as $item) {
            $this->object[] = (object) $item;
        }
        return $this->object;
    }

    private function selectMaker(): string
    {
        if (count($this->fields)) {
            return 'SELECT ' . implode(', ', $this->fields) . ' FROM ';
        }
        return 'SELECT * FROM ';
    }
}
