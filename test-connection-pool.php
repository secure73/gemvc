<?php
require_once 'vendor/autoload.php';

use Gemvc\Database\PdoConnection;
use Gemvc\Database\QueryExecuter;

// Environment variables will be automatically used if defined in .env file
// Otherwise, default values from PdoConnection class will be used
// Uncomment to manually set pool size for testing:
// PdoConnection::setMaxPoolSize(3);
// PdoConnection::setMinPoolSize(1);

echo "GEMVC Connection Pool Test\n";
echo "-------------------------\n";
echo "Min Pool Size: " . PdoConnection::getMinPoolSize() . "\n";
echo "Max Pool Size: " . PdoConnection::getMaxPoolSize() . "\n";
echo "Max Connection Age: " . PdoConnection::getMaxConnectionAge() . " seconds\n";

// Test 1: Basic query
echo "\nTest 1: Basic query with pooling\n";
$qe1 = new QueryExecuter(); // Connection pooling handled by PdoConnection
$qe1->query('SELECT 1 as test');
$qe1->execute();
$result = $qe1->fetchAll();
echo "Result: " . json_encode($result) . "\n";
echo "Pool size after first query: " . PdoConnection::getPoolSize() . "\n";
echo "Total connections: " . PdoConnection::getTotalConnections() . "\n";
$qe1->secure(); // Explicitly release connection
echo "Pool size after release: " . PdoConnection::getPoolSize() . "\n";
echo "Total connections after release: " . PdoConnection::getTotalConnections() . "\n";

// Test 2: Multiple queries
echo "\nTest 2: Multiple queries\n";
for ($i = 0; $i < 5; $i++) {
    echo "Query iteration $i: ";
    $qe = new QueryExecuter();
    $qe->query('SELECT ' . ($i + 1) . ' as iteration');
    $qe->execute();
    $result = $qe->fetchAll();
    echo json_encode($result) . "\n";
    $qe->secure();
}
echo "Pool size after multiple queries: " . PdoConnection::getPoolSize() . "\n";
echo "Total connections: " . PdoConnection::getTotalConnections() . "\n";

// Test 3: Connection cleanup (removed disabling pooling test)
echo "\nTest 3: Clean expired connections\n";
// Force some connections to expire by setting the time back
$expiredConnections = PdoConnection::cleanExpiredConnections();
echo "Expired connections removed: " . $expiredConnections . "\n";
echo "Pool size after cleaning: " . PdoConnection::getPoolSize() . "\n";
echo "Total connections: " . PdoConnection::getTotalConnections() . "\n";

// Test 4: Clear pool
echo "\nTest 4: Clear pool\n";
PdoConnection::clearPool();
echo "Pool size after clearing: " . PdoConnection::getPoolSize() . "\n";
echo "Total connections: " . PdoConnection::getTotalConnections() . "\n";

// Test 5: Create new connections after clearing
echo "\nTest 5: Create new connections after clearing\n";
for ($i = 0; $i < 3; $i++) {
    $qe = new QueryExecuter();
    $qe->query('SELECT ' . ($i + 1) . ' as new_connection');
    $qe->execute();
    $result = $qe->fetchAll();
    echo "Result $i: " . json_encode($result) . "\n";
    $qe->secure();
}
echo "Final pool size: " . PdoConnection::getPoolSize() . "\n";
echo "Total connections: " . PdoConnection::getTotalConnections() . "\n";

// Test 6: Check error handling
echo "\nTest 6: Error handling\n";
$qe6 = new QueryExecuter();
$qe6->query('INVALID SQL QUERY');
$result = $qe6->execute();
echo "Execution result: " . ($result ? "success" : "failure") . "\n";
echo "Error status: " . ($qe6->getError() !== null ? $qe6->getError() : 'No errors') . "\n";
$qe6->secure();

echo "\nTest completed.\n"; 