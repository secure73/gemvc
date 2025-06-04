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

use Gemvc\Database\QueryBuilderInterface;
use Gemvc\Database\PdoQuery;
use Gemvc\Database\QueryBuilder;


class Delete  implements QueryBuilderInterface
{
    use WhereTrait;

    public ?int $result;

    public string $query;

    /**
     * @var array<mixed>
     */
    public $arrayBindValues = [];

    private string $table;

    /**
     * @var array<string>
     */
    private $whereConditions = [];

    private string $_query;
    
    /**
     * Store the last error message
     */
    private ?string $_lastError = null;
    
    /**
     * Reference to the query builder that created this delete query
     */
    private ?QueryBuilder $queryBuilder = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Build the DELETE query string
     */
    public function __toString(): string
    {
        if (empty($this->whereConditions)) {
            // Prevent DELETE without WHERE clause for safety
            $this->_query = 'DELETE FROM ' . $this->table . ' WHERE 1=0'; // Safe no-op
        } else {
            $this->_query = 'DELETE FROM ' . $this->table . ' WHERE ' . implode(' AND ', $this->whereConditions);
        }

        return $this->_query;
    }

    /**
     * Set the query builder reference
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    /**
     * Execute the DELETE query and return the number of affected rows
     * Following our unified return pattern: result|null
     * 
     * @return int|null Number of affected rows on success, null on error
     */
    public function run(): int|null
    {
        // Validate that we have WHERE conditions for safety
        if (empty($this->whereConditions)) {
            $this->_lastError = "DELETE queries must have WHERE conditions for safety";
            $this->registerWithBuilder();
            return null;
        }

        // Use the shared PdoQuery instance from QueryBuilder if available
        $pdoQuery = $this->queryBuilder ? $this->queryBuilder->getPdoQuery() : new PdoQuery();
        
        $query = $this->__toString();
        $result = $pdoQuery->deleteQuery($query, $this->arrayBindValues);
        
        if ($result === null) {
            $this->_lastError = $pdoQuery->getError();
            $this->registerWithBuilder();
            return null;
        }
        
        $this->registerWithBuilder();
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
     * Register this query with the builder for error tracking
     */
    private function registerWithBuilder(): void
    {
        if ($this->queryBuilder !== null) {
            $this->queryBuilder->setLastQuery($this);
        }
    }
}
