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

    private ?int $limit = null;
    private ?int $offset = null;

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
     * @return array<mixed>|false
     */
    public function run(): array|false
    {
        $pdoQuery = new PdoQuery();
        $query = $this->__toString();
        $result = $pdoQuery->selectQuery($query, $this->arrayBindValues);
        if ($result === false) {
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
    

    public function json(): string|false
    {
        $pdoQuery = new PdoQuery();
        $array = [];
        $query = $this->__toString();
        $result = $pdoQuery->selectQuery($query, $this->arrayBindValues);
        if ($result === false) {
            $this->_lastError = $pdoQuery->getError();
            return false;
        }
        if (\is_array($result)) {
            foreach ($result as $item) {
                $encoded = json_encode($item, JSON_PRETTY_PRINT);
                if ($encoded) {
                    $array[] = json_decode($encoded);
                }
            }
        }
        return json_encode($array, JSON_PRETTY_PRINT);
    }

    /**
     * @param  PdoQuery $classTable
     * @return array<mixed>
     */
    public function object(PdoQuery $classTable): array
    {
        $query = $this->__toString();
        $result = $classTable->selectQuery($query, $this->arrayBindValues);
        if ($result === false) {
            $this->_lastError = $classTable->getError();
            return [];
        }
        if (\is_array($result)) {
            foreach ($result as $item) {
                if (\is_array($item)) {
                    $this->object[] = (object) $item;
                }
            }
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
