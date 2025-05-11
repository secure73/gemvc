<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Gemvc\Database\QueryBuilder;

// Example of using the Select query
$queryBuilder = new QueryBuilder();

// Example 1: Basic select
$select = $queryBuilder->select('id', 'name', 'email')
    ->from('users')
    ->where('status', '=', 'active')
    ->orderBy('name')
    ->limit(10);

// Get the SQL string for debugging
echo "SQL Query: " . $select . PHP_EOL;

// Execute the query
$results = $select->run();

if ($results === false) {
    echo "Error: " . $select->getError() . PHP_EOL;
} else {
    echo "Found " . count($results) . " users:" . PHP_EOL;
    foreach ($results as $user) {
        echo "- {$user['name']} ({$user['email']})" . PHP_EOL;
    }
}

// Example 2: Join query
$joinSelect = $queryBuilder->select('u.id', 'u.name', 'p.title')
    ->from('users', 'u')
    ->leftJoin('posts p ON u.id = p.user_id')
    ->where('u.status', '=', 'active')
    ->run();

// Example 3: Get results as JSON
$jsonSelect = $queryBuilder->select('id', 'name', 'email')
    ->from('users')
    ->where('created_at', '>', '2023-01-01')
    ->limit(5);

$jsonResult = $jsonSelect->json();
if ($jsonResult !== false) {
    echo "\nJSON Result:\n" . $jsonResult . PHP_EOL;
} else {
    echo "Error getting JSON: " . $jsonSelect->getError() . PHP_EOL;
}

// Example 4: Error handling
$errorSelect = $queryBuilder->select()
    ->from('non_existent_table')
    ->run();

if ($errorSelect === false) {
    echo "\nExpected error: " . $select->getError() . PHP_EOL;
} 