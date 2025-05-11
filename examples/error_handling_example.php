<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gemvc\Database\QueryBuilder;

// Example of using the improved error handling
$queryBuilder = new QueryBuilder();

echo "CHAINED QUERY EXAMPLES WITH ERROR HANDLING\n";
echo "----------------------------------------\n\n";

// Example 1: Basic chained delete with error check from QueryBuilder
echo "Example 1: Chained delete query\n";
$deleteResult = $queryBuilder->delete('non_existent_table')
    ->where('id', '=', 1)
    ->run();

if ($deleteResult === false) {
    // We can now get the error from the QueryBuilder
    echo "Error: " . $queryBuilder->getError() . "\n\n";
} else {
    echo "Deleted $deleteResult rows\n\n";
}

// Example 2: Chained select with error check
echo "Example 2: Chained select query\n";
$selectResult = $queryBuilder->select('id', 'name')
    ->from('another_missing_table')
    ->where('status', '=', 'active')
    ->run();

if ($selectResult === false) {
    echo "Error: " . $queryBuilder->getError() . "\n\n";
} else {
    echo "Found " . count($selectResult) . " rows\n\n";
}

// Example 3: Multiple queries with error tracking
echo "Example 3: Multiple sequential queries with errors\n";

// First query (should fail)
$queryBuilder->select()->from('table1')->run();
echo "First query error: " . $queryBuilder->getError() . "\n";

// Second query (should also fail with different error)
$queryBuilder->insert('table2')->columns('id')->values(1)->run();
echo "Second query error: " . $queryBuilder->getError() . "\n\n";

// Example 4: Store object reference if needed for multiple operations
echo "Example 4: Storing query object for multiple operations\n";
$query = $queryBuilder->update('users')
    ->set('status', 'inactive')
    ->where('last_login', '<', date('Y-m-d', strtotime('-90 days')));

// Get the SQL for debugging
echo "SQL: " . $query . "\n";

// Execute later
$result = $query->run();

// Can check errors from either the query object or the builder
echo "Error from query object: " . $query->getError() . "\n";
echo "Error from builder: " . $queryBuilder->getError() . "\n";

echo "\nDone!\n"; 