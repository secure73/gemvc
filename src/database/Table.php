<?php

namespace Gemvc\Database;

use Gemvc\Database\PdoQuery;

/**
 * Base table class for database operations
 * 
 * Provides a fluent interface for database queries and operations
 */
class Table extends PdoQuery
{
    /** @var string|null SQL query being built */
    private ?string $_query = null;
    
    /** @var bool Whether a SELECT query has been initiated */
    private bool $_isSelectSet = false;
    
    /** @var bool Whether to apply limits to the query */
    private bool $_no_limit = false;
    
    /** @var array<string,mixed> Query parameter bindings */
    private array $_binds = [];
    
    /** @var int Number of rows per page */
    private int $_limit;
    
    /** @var int Pagination offset */
    private int $_offset = 0;
    
    /** @var string ORDER BY clause */
    private string $_orderBy = '';
    
    /** @var int Total count of rows from last query */
    private int $_total_count = 0;
    
    /** @var int Number of pages from last query */
    private int $_count_pages = 0;
    
    /** @var array<string> WHERE clauses */
    private array $_arr_where = [];
    
    /** @var array<string> JOIN clauses */
    private array $_joins = [];

    /**
     * Initialize a new Table instance
     */
    public function __construct()
    {
        $this->_limit = (isset($_ENV['QUERY_LIMIT']) && is_numeric($_ENV['QUERY_LIMIT'])) 
            ? (int)$_ENV['QUERY_LIMIT'] 
            : 10;
            
        parent::__construct();
    }

    /**
     * Validate essential properties and show error if not valid
     * 
     * @param array<string> $properties Properties to validate
     * @return bool True if all properties exist
     */
    protected function validateProperties(array $properties): bool 
    {
        $table = $this->getTable();
        if (!$table) {
            $this->setError('Table name is not set in function getTable()');
            return false;
        }

        foreach ($properties as $property) {
            if (!property_exists($this, $property)) {
                $this->setError("Property '{$property}' is not set in table");
                return false;
            }
        }
        
        return true;
    }

    /*
     * =============================================
     * CRUD OPERATIONS
     * =============================================
     */

    /**
     * Inserts a single row into the database table
     * 
     * @return null|static The current instance with inserted id
     */
    public function insertSingleQuery(): null|static
    {
        $this->validateProperties([]);

        $query = $this->buildInsertQuery();
        $arrayBind = $this->getInsertBindings();
        
        $result = $this->insertQuery($query, $arrayBind);
        
        if ($this->getError() || $result === false) {
            $this->setError("Error in insert query: ".$this->getTable() .":". $this->getError());
            return null;
        }
        
        if (property_exists($this, 'id')) {
            $this->id = $result;
        }
        return $this;
    }

    /**
     * Updates a record based on its ID property
     * 
     * @return null|static Current instance
     */
    public function updateSingleQuery(): null|static
    {
        if(!property_exists($this, 'id')){
            $this->setError("Property 'id' is not exists in object ");
            return null;
        }
        if ($this->id < 1) {
            $this->setError("ID must be a positive integer for update in {$this->getTable()}");
            return null;
        }
        $id = $this->id;
        
        $arrayBind = [];
        $query = "UPDATE {$this->getTable()} SET ";
        
        foreach ($this as $key => $value) {
            $query .= " {$key} = :{$key},";
            $arrayBind[":{$key}"] = $value;
        }
        
        $query = rtrim($query, ',');
        $query .= ' WHERE id = :id';
        $arrayBind[':id'] = $id;
        
        $result = $this->updateQuery($query, $arrayBind);
        
        if ($result === false || $this->getError()) {
            $this->setError("Error in update query: {$this->getTable()}: " . $this->getError());
            return null;
        }
        
        return $this;
    }

    /**
     * Deletes a record by ID and return id for deleted object
     * 
     * @param int $id Record ID to delete
     * @return null|int
     */
    public function deleteByIdQuery(int $id): null|int
    {
        if(!property_exists($this, 'id')){
            $this->setError("Property 'id' is not exists in object ");
            return null;
        }
        if ($id < 1) {
            $this->setError("ID must be a positive integer for update in {$this->getTable()}");
            return null;
        }
              
        $query = "DELETE FROM {$this->getTable()} WHERE id = :id";
        $result = $this->deleteQuery($query, [':id' => $id]);
        
        if ($this->getError() || !is_int($result)) {
            $this->setError("Error in delete query: {$this->getTable()} - {$this->getError()}");
            return null;
        }
        return $id;
    }

    /**
     * Marks a record as deleted (soft delete)
     * @return null|static Current instance
     */
    public function safeDeleteQuery(): null|static
    {
        if(!property_exists($this, 'id')){
            $this->setError("Property 'id' is not exists in object ");
            return null;
        }
        if ($this->id < 1) {
            $this->setError("id must be a positive integer for update in {$this->getTable()}");
            return null;
        }
        $valid = $this->validateProperties(['deleted_at']);
        if(!$valid){
            $this->setError("for safe delete deleted_at must be existed in Database table and object must have deleted_at property");
            return null;
        }
        $id = $this->id;
        $query = "UPDATE {$this->getTable()} SET deleted_at = NOW() WHERE id = :id";
        
        if (property_exists($this, 'is_active')) {
            $query = "UPDATE {$this->getTable()} SET deleted_at = NOW(), is_active = 0 WHERE id = :id";
        }
        
        $result = $this->updateQuery($query, [':id' => $id]);
        
        if (!$result  || $this->getError()) {
            $this->setError("Error in safeDelete operation: " . $this->getError());
            return null;
        }
        $this->deleted_at = date('Y-m-d H:i:s');
        $this->is_active = 0;
        
        return $this;
    }

    /**
     * Restores a soft-deleted record
     * 
     * @return null|static Current instance
     */
    public function restoreQuery(): null|static
    {
        if(!property_exists($this, 'id')){
            $this->setError("Property 'id' is not exists in object ");
            return null;
        }
        if ($this->id < 1) {
            $this->setError("id must be a positive integer for update in {$this->getTable()}");
            return null;
        }
        $valid = $this->validateProperties(['deleted_at']);
        if(!$valid){
            $this->setError("for safe delete deleted_at must be existed in Database table and object must have deleted_at property");
            return null;
        }
        $id = $this->id;
        $query = "UPDATE {$this->getTable()} SET deleted_at = NULL WHERE id = :id";
               
        $result = $this->updateQuery($query, [':id' => $id]);
        
        if (!$result  || $this->getError()) {
            $this->setError("Error in safeDelete operation: " . $this->getError());
            return null;
        }
        $this->deleted_at = null;       
        return $this;
    }

    /**
     * Removes an object from the database by ID
     * 
     * @param int $id Record ID to remove
     * @return int Number of affected rows
     */
    public function deleteSingleQuery(): null|int
    {
        if(!property_exists($this, 'id')){
            $this->setError("Property 'id' is not exists in object ");
            return null;
        }
        if ($this->id < 1) {
            $this->setError("id must be a positive integer for update in {$this->getTable()}");
            return null;
        }
        return $this->removeConditionalQuery('id', $this->id);
    }

    /**
     * Removes records based on conditional WHERE clauses
     * 
     * @param string $whereColumn Primary column for WHERE condition
     * @param mixed $whereValue Value to match in primary column
     * @param string|null $secondWhereColumn Optional second column for WHERE condition
     * @param mixed $secondWhereValue Value to match in second column
     * @return int Number of affected rows
     */
    public function removeConditionalQuery(
        string $whereColumn, 
        mixed $whereValue, 
        ?string $secondWhereColumn = null, 
        mixed $secondWhereValue = null
    ): null|int {
        $this->validateProperties([]);

        $query = "DELETE FROM {$this->getTable()} WHERE {$whereColumn} = :{$whereColumn}";
        
        if ($secondWhereColumn) {
            $query .= " AND {$secondWhereColumn} = :{$secondWhereColumn}";
        }

        $arrayBind = [':' . $whereColumn => $whereValue];
        
        if ($secondWhereColumn) {
            $arrayBind[':' . $secondWhereColumn] = $secondWhereValue;
        }
        
        $result = $this->deleteQuery($query, $arrayBind);

        if ($this->getError()) { 
            $this->setError("Error in delete query: {$this->getTable()} - {$this->getError()}");
            return null;
        }
       
        return $result;
    }
    
    /*
     * =============================================
     * QUERY BUILDING METHODS - SELECT
     * =============================================
     */

    /**
     * Starts building a SELECT query
     * 
     * @param string|null $columns Columns to select (defaults to *)
     * @return self For method chaining
     */
    public function select(string $columns = null): self
    {
        if (!$this->_isSelectSet) {
            $this->_query = $columns ? "SELECT $columns " : "SELECT * ";
            $this->_isSelectSet = true;
        } else {
            // If select is called again, append the new columns
            $this->_query .= $columns ? ", $columns" : "";
        }
        return $this;
    }

    /**
     * Adds a JOIN clause to the query
     * 
     * @param string $table Table to join
     * @param string $condition Join condition (ON clause)
     * @param string $type Join type (INNER, LEFT, RIGHT, etc.)
     * @return self For method chaining
     */
    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->_joins[] = strtoupper($type) . " JOIN $table ON $condition";
        return $this;
    }

    /**
     * Sets the results limit for pagination
     * 
     * @param int $limit Maximum number of rows to return
     * @return self For method chaining
     */
    public function limit(int $limit): self
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * Disables pagination limits
     * 
     * @return self For method chaining
     */
    public function noLimit(): self
    {
        $this->_no_limit = true;
        return $this;
    }

    /**
     * Alias for noLimit() - returns all results
     * 
     * @return self For method chaining
     */
    public function all(): self
    {
        $this->_no_limit = true;
        return $this;
    }

    /**
     * Adds an ORDER BY clause to the query
     * 
     * @param string|null $columnName Column to sort by (defaults to 'id')
     * @param bool|null $ascending Whether to sort in ascending order (true) or descending (false/null)
     * @return self For method chaining
     */
    public function orderBy(string $columnName = null, bool $ascending = null): self
    {
        $columnName = $columnName ?: 'id';
        $ascending = $ascending ? ' ASC ' : ' DESC ';
        $this->_orderBy .= " ORDER BY {$columnName}{$ascending}";
        return $this;
    }
    
    /*
     * =============================================
     * WHERE CLAUSES
     * =============================================
     */

    /**
     * Adds a basic WHERE equality condition
     * 
     * @param string $column Column name
     * @param mixed $value Value to match
     * @return self For method chaining
     */
    public function where(string $column, mixed $value): self
    {
        $this->_arr_where[] = count($this->_arr_where) 
            ? " AND {$column} = :{$column} " 
            : " WHERE {$column} = :{$column} ";
            
        $this->_binds[':' . $column] = $value;
        return $this;
    }

    /**
     * Adds a LIKE condition with wildcard after the value
     * 
     * @param string $column Column name
     * @param string $value Value to match (% will be appended)
     * @return self For method chaining
     */
    public function whereLike(string $column, string $value): self
    {
        $this->_arr_where[] = count($this->_arr_where) 
            ? " AND {$column} LIKE :{$column} " 
            : " WHERE {$column} LIKE :{$column} ";
            
        $this->_binds[':' . $column] = $value . '%';
        return $this;
    }

    /**
     * Adds a LIKE condition with wildcard before the value
     * 
     * @param string $column Column name
     * @param string $value Value to match (% will be prepended)
     * @return self For method chaining
     */
    public function whereLikeLast(string $column, string $value): self
    {
        $this->_arr_where[] = count($this->_arr_where) 
            ? " AND {$column} LIKE :{$column} " 
            : " WHERE {$column} LIKE :{$column} ";
            
        $this->_binds[':' . $column] = '%' . $value;
        return $this;
    }

    /**
     * Adds a BETWEEN condition
     * 
     * @param string $columnName Column name
     * @param int|string|float $lowerBand Lower bound value
     * @param int|string|float $higherBand Upper bound value
     * @return self For method chaining
     */
    public function whereBetween(
        string $columnName, 
        int|string|float $lowerBand, 
        int|string|float $higherBand
    ): self {
        $colLower = ':' . $columnName . 'lowerBand';
        $colHigher = ':' . $columnName . 'higherBand';

        $this->_arr_where[] = count($this->_arr_where) 
            ? " AND {$columnName} BETWEEN {$colLower} AND {$colHigher} " 
            : " WHERE {$columnName} BETWEEN {$colLower} AND {$colHigher} ";
            
        $this->_binds[$colLower] = $lowerBand;
        $this->_binds[$colHigher] = $higherBand;
        return $this;
    }

    /**
     * Adds a WHERE IS NULL condition
     * 
     * @param string $column Column name
     * @return self For method chaining
     */
    public function whereNull(string $column): self
    {
        $this->_arr_where[] = count($this->_arr_where) 
            ? " AND {$column} IS NULL " 
            : " WHERE {$column} IS NULL ";
            
        return $this;
    }

    /**
     * Adds a WHERE IS NOT NULL condition
     * 
     * @param string $column Column name
     * @return self For method chaining
     */
    public function whereNotNull(string $column): self
    {
        $this->_arr_where[] = count($this->_arr_where) 
            ? " AND {$column} IS NOT NULL " 
            : " WHERE {$column} IS NOT NULL ";
            
        return $this;
    }
    
    /*
     * =============================================
     * SPECIALIZED UPDATE OPERATIONS
     * =============================================
     */

    /**
     * Sets a column to NULL based on a WHERE condition
     * 
     * @param string $columnNameSetToNull Column to set to NULL
     * @param string $whereColumn WHERE condition column
     * @param mixed $whereValue WHERE condition value
     * @return null|int Number of affected rows
     */
    public function setNullQuery(string $columnNameSetToNull, string $whereColumn, mixed $whereValue): null|int
    {
        $this->validateProperties([]);

        $query = "UPDATE {$this->getTable()} SET {$columnNameSetToNull} = NULL WHERE {$whereColumn} = :whereValue";
        $result = $this->updateQuery($query, [':whereValue' => $whereValue]);
        
        if ($this->getError() || $result === false) {
            $this->setError("Error in update query: {$this->getTable()}: " . $this->getError());
            return null;
        }
        
        return $result;
    }

    /**
     * Sets a column to current timestamp based on a WHERE condition
     * 
     * @param string $columnNameSetToNowTomeStamp Column to set to NOW()
     * @param string $whereColumn WHERE condition column
     * @param mixed $whereValue WHERE condition value
     * @return null|int Number of affected rows
     */
    public function setTimeNowQuery(string $columnNameSetToNowTomeStamp, string $whereColumn, mixed $whereValue): null|int
    {
        $this->validateProperties([]);

        $query = "UPDATE {$this->getTable()} SET {$columnNameSetToNowTomeStamp} = NOW() WHERE {$whereColumn} = :whereValue";
        $result = $this->updateQuery($query, [':whereValue' => $whereValue]);
        
        if ($this->getError() || $result === false) {
            $this->setError("Error in update query: {$this->getTable()}: " . $this->getError());
            return null;
        }
        
        return $result;
    }

    /**
     * Sets is_active to 1 (activate record)
     * 
     * @param int $id Record ID to activate
     * @return null|int Number of affected rows
     */
    public function activateQuery(int $id): null|int
    {
        $this->validateProperties(['is_active']);

        if ($id < 1) {
            $this->setError('ID must be a positive integer');
            return null;
        }
        
        $result = $this->updateQuery(
            "UPDATE {$this->getTable()} SET is_active = 1 WHERE id = :id", 
            [':id' => $id]
        );
        
        if ($result === false || $this->getError()) {
            $this->setError("Error in activateQuery: " . $this->getError());
            return null;
        }
        
        return $result;
    }

    /**
     * Sets is_active to 0 (deactivate record)
     * 
     * @param int $id Record ID to deactivate
     * @return null|int Number of affected rows
     */
    public function deactivateQuery(int $id): null|int
    {
        $this->validateProperties(['is_active']);

        if ($id < 1) {
            $this->setError('ID must be a positive integer');
            return null;
        }
        
        $result = $this->updateQuery(
            "UPDATE {$this->getTable()} SET is_active = 0 WHERE id = :id", 
            [':id' => $id]
        );
        
        if ($result === false || $this->getError()) {
            $this->setError("Error in deactivateQuery: " . $this->getError());
            return null;
        }
        
        return $result;
    }
    
    /*
     * =============================================
     * FETCH OPERATIONS
     * =============================================
     */

    /**
     * Selects a single row by ID
     * 
     * @param int $id Record ID to select
     * @return null|static Found instance or null if not found
     */
    public function selectById(int $id): null|static
    {
        $result = $this->select()->where('id', $id)->limit(1)->run();
        
        if ($this->getError()) {
            $this->setError(get_class($this) . ": Failed to select: " . $this->getError());
            return null;
        }
        
        if (count($result) === 0) {
            $this->setError('Nothing found');
            return null;
        }
        
        return $result[0];
    }

    /**
     * Executes a SELECT query and returns results
     * 
     * @return array<static> Array of model instances
     */
    public function run(): null|array
    {
        $objectName = get_class($this);
        
        if (!$this->_isSelectSet) {
            $this->setError('Before any chain function you shall first use select()');
            return null;
        }

        if ($this->getError()) {
            $this->setError("Error in table class for $objectName: " . $this->getError());
            return null;
        }

        $this->buildCompleteQuery();
        $queryResult = $this->executeSelectQuery();
        
        if (!count($queryResult)) {
            return [];
        }
        
        return $this->hydrateResults($queryResult);
    }
    
    /*
     * =============================================
     * PAGINATION METHODS
     * =============================================
     */

    /**
     * Sets the current page for pagination
     * 
     * @param int $page Page number (1-based)
     * @return void
     */
    public function setPage(int $page): void
    {
        $page = $page < 1 ? 0 : $page - 1;
        $this->_offset = $page * $this->_limit;
    }

    /**
     * Gets the current page number
     * 
     * @return int Current page (1-based)
     */
    public function getCurrentPage(): int
    {
        return $this->_offset + 1;
    }

    /**
     * Gets the number of pages from the last query
     * 
     * @return int Page count
     */
    public function getCount(): int
    {
        return $this->_count_pages;
    }

    /**
     * Gets the total number of records from the last query
     * 
     * @return int Total count
     */
    public function getTotalCounts(): int
    {
        return $this->_total_count;
    }

    /**
     * Gets the current limit per page
     * 
     * @return int Current limit
     */
    public function getLimit(): int
    {
        return $this->_limit;
    }
    
    /*
     * =============================================
     * HELPER METHODS
     * =============================================
     */

    /**
     * Must be implemented by child classes to specify the table name
     * 
     * @return string Table name
     */
    public function getTable(): string
    {
        return '';
    }

    /**
     * Gets the current query string
     * 
     * @return string|null Current query
     */
    public function getQuery(): string|null
    {
        return $this->_query;
    }

    /**
     * Gets the current parameter bindings
     * 
     * @return array<mixed> Current bindings
     */
    public function getBind(): array
    {
        return $this->_binds;
    }

    /**
     * Gets the current SELECT query string
     * 
     * @return string|null Current SELECT query
     */
    public function getSelectQueryString(): string|null
    {
        return $this->_query;
    }

    /**
     * Hydrates model properties from database row
     * 
     * @param array<mixed> $row Database row
     * @return void
     */
    protected function fetchRow(array $row): void
    {
        foreach ($row as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /*
     * =============================================
     * PRIVATE HELPER METHODS
     * =============================================
     */
    
    /**
     * Builds a complete WHERE clause from stored conditions
     * 
     * @return string WHERE clause
     */
    private function whereMaker(): string
    {
        if (!count($this->_arr_where)) {
            return ' WHERE 1 ';
        }
        
        $query = ' ';
        
        foreach ($this->_arr_where as $value) {
            $query .= ' ' . $value . ' ';
        }
        
        return trim($query);
    }
    
    /**
     * Builds bindings for INSERT operation
     * 
     * @return array<string,mixed> Bindings for insert query
     */
    private function getInsertBindings(): array
    {
        $arrayBind = [];
        
        foreach ($this as $key => $value) {
            if ($key[0] === '_') {
                continue;
            }
            $arrayBind[':' . $key] = $value;
        }
        
        return $arrayBind;
    }
    
    /**
     * Builds an INSERT query
     * 
     * @return string Complete INSERT query
     */
    private function buildInsertQuery(): string
    {
        $columns = '';
        $params = '';
        
        foreach ($this as $key => $value) {
            if ($key[0] === '_') {
                continue;
            }
            $columns .= $key . ',';
            $params .= ':' . $key . ',';
        }

        $columns = rtrim($columns, ',');
        $params = rtrim($params, ',');

        return "INSERT INTO {$this->getTable()} ({$columns}) VALUES ({$params})";
    }
    
    /**
     * Builds an UPDATE query with bindings
     * 
     * @param string $idWhereKey Column for WHERE clause
     * @param mixed $idWhereValue Value for WHERE clause
     * @return array{0: string, 1: array<string,mixed>} Query and bindings
     */
    private function buildUpdateQuery(string $idWhereKey, mixed $idWhereValue): array
    {
        $query = "UPDATE {$this->getTable()} SET ";
        $arrayBind = [];          
        
        foreach ($this as $key => $value) {
            if ($key[0] === '_' || $key === $idWhereKey) {
                continue;
            }
            
            $query .= " {$key} = :{$key},";
            $arrayBind[":{$key}"] = $value;
        }

        $query = rtrim($query, ',');
        $query .= " WHERE {$idWhereKey} = :{$idWhereKey} ";
        $arrayBind[":{$idWhereKey}"] = $idWhereValue;
        
        return [$query, $arrayBind];
    }
    
    /**
     * Builds the complete SELECT query
     * 
     * @return void
     */
    private function buildCompleteQuery(): void
    {
        $joinClause = implode(' ', $this->_joins);
        $whereClause = $this->whereMaker();

        $this->_query = $this->_query .
            " , (SELECT COUNT(*) FROM {$this->getTable()} $joinClause $whereClause) AS _total_count " .
            "FROM {$this->getTable()} $joinClause $whereClause ";

        if (!$this->_no_limit) {
            $this->_query .= $this->_orderBy . " LIMIT {$this->_limit} OFFSET {$this->_offset} ";
        } else {
            $this->_query .= $this->_orderBy;
        }

        $this->_query = trim($this->_query);
        $this->_query = preg_replace('/\s+/', ' ', $this->_query);
        
        if (!$this->_query) {
            $this->setError("Given query-string is not acceptable: " . $this->getError());
            return;
        }
    }
    
    /**
     * Executes the SELECT query
     * 
     * @return array<mixed> Query results
     */
    private function executeSelectQuery(): null|array
    {
        $queryResult = $this->selectQuery($this->_query, $this->_binds);
        
        if (!is_array($queryResult)) {
            $this->setError("Error executing SELECT query for " . get_class($this) . ": " . $this->getError());
            return null;
        }
        
        return $queryResult;
    }
    
    /**
     * Hydrates model instances from query results
     * 
     * @param array<mixed> $queryResult Query results
     * @return array<static> Hydrated model instances
     */
    private function hydrateResults(array $queryResult): array
    {
        $object_result = [];
        $this->_total_count = (int)$queryResult[0]['_total_count'];
        $this->_count_pages = round($this->_total_count / $this->_limit);
        
        foreach ($queryResult as $item) {
            unset($item['_total_count']);
            $instance = new $this();
            if (is_array($item)) {
                $instance->fetchRow($item);
            }
            $object_result[] = $instance;
        }
        
        return $object_result;
    }
}
