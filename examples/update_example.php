<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gemvc\Database\QueryBuilder;

// Example of using the Update query
$queryBuilder = new QueryBuilder();

// Example 1: Basic update
$update = $queryBuilder->update('users')
    ->set('status', 'inactive')
    ->set('updated_at', date('Y-m-d H:i:s'))
    ->where('last_login', '<', date('Y-m-d', strtotime('-90 days')));

// Get the SQL string for debugging
echo "SQL Query: " . $update . PHP_EOL;

// Execute the query
$result = $update->run();

if ($result === false) {
    echo "Error: " . $update->getError() . PHP_EOL;
} else {
    echo "Updated " . $result . " user(s) successfully" . PHP_EOL;
}

// Example 2: Update with multiple conditions
$complexUpdate = $queryBuilder->update('products')
    ->set('price', 19.99)
    ->set('on_sale', true)
    ->where('category', '=', 'electronics')
    ->where('stock', '>', 0);

$complexResult = $complexUpdate->run();

if ($complexResult === false) {
    echo "\nError: " . $complexUpdate->getError() . PHP_EOL;
} else {
    echo "\nUpdated " . $complexResult . " product(s) successfully" . PHP_EOL;
}

// Example 3: Update with error handling
$badUpdate = $queryBuilder->update('non_existent_table')
    ->set('name', 'test')
    ->where('id', '=', 1);

$badResult = $badUpdate->run();

if ($badResult === false) {
    echo "\nExpected error: " . $badUpdate->getError() . PHP_EOL;
} 