<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gemvc\Database\QueryBuilder;

// Example of using the Delete query
$queryBuilder = new QueryBuilder();

// Example 1: Basic delete
$delete = $queryBuilder->delete('users')
    ->where('status', '=', 'inactive')
    ->where('last_login', '<', date('Y-m-d', strtotime('-365 days')));

// Get the SQL string for debugging
echo "SQL Query: " . $delete . PHP_EOL;

// Execute the query
$result = $delete->run();

if ($result === false) {
    echo "Error: " . $delete->getError() . PHP_EOL;
} else {
    echo "Deleted " . $result . " inactive user(s) successfully" . PHP_EOL;
}

// Example 2: Delete with exact condition
$exactDelete = $queryBuilder->delete('temp_logs')
    ->where('created_at', '<', date('Y-m-d', strtotime('-30 days')));

$exactResult = $exactDelete->run();

if ($exactResult === false) {
    echo "\nError: " . $exactDelete->getError() . PHP_EOL;
} else {
    echo "\nDeleted " . $exactResult . " old log entries successfully" . PHP_EOL;
}

// Example 3: Delete with error handling
$badDelete = $queryBuilder->delete('non_existent_table')
    ->where('id', '=', 1);

$badResult = $badDelete->run();

if ($badResult === false) {
    echo "\nExpected error: " . $badDelete->getError() . PHP_EOL;
} 