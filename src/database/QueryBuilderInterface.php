<?php
namespace Gemvc\Database;

/**
 * Enhanced Query Builder Interface for consistent query execution
 * 
 * All query objects must implement this interface to ensure consistent
 * error handling and return patterns across the entire database layer.
 */
interface QueryBuilderInterface
{
    /**
     * Execute the query and return results
     * 
     * Following our unified return pattern:
     * - Returns meaningful result data on success
     * - Returns null on error (check getError() for details)
     * 
     * @return mixed Query results on success, null on error
     */
    public function run(): mixed;

    /**
     * Get the last error message if any
     * 
     * @return string|null Error message or null if no error occurred
     */
    public function getError(): ?string;

    /**
     * Set a reference to the query builder that created this query
     * This enables proper error tracking and shared connection management
     * 
     * @param QueryBuilder $queryBuilder The parent query builder
     * @return self For method chaining
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): self;
}
