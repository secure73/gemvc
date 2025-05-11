<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gemvc\Database\QueryBuilder;

// Example of using the Insert query
$queryBuilder = new QueryBuilder();

// Example 1: Basic insert
$insert = $queryBuilder->insert('users')
    ->columns('name', 'email', 'created_at')
    ->values('John Doe', 'john@example.com', date('Y-m-d H:i:s'));

// Get the SQL string for debugging
echo "SQL Query: " . $insert . PHP_EOL;

// Execute the query
$result = $insert->run();

if ($result === false) {
    echo "Error: " . $insert->getError() . PHP_EOL;
} else {
    echo "User inserted successfully with ID: " . $result . PHP_EOL;
}

// Example 2: Insert with error handling
$badInsert = $queryBuilder->insert('non_existent_table')
    ->columns('name', 'value')
    ->values('test', 123);

$badResult = $badInsert->run();

if ($badResult === false) {
    echo "\nExpected error: " . $badInsert->getError() . PHP_EOL;
} 