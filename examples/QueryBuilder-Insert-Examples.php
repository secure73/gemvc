<?php

/**
 * QueryBuilder INSERT Examples
 * 
 * Comprehensive examples showing how to use the enhanced INSERT functionality
 */

require_once 'src/database/QueryBuilder.php';
use Gemvc\Database\QueryBuilder;

echo "=== QueryBuilder INSERT Examples ===\n\n";

$queryBuilder = new QueryBuilder();

// =============================================================================
// Example 1: Basic INSERT
// =============================================================================

echo "1. Basic User INSERT:\n";
echo "---------------------\n";

$insertId = $queryBuilder->insert('users')
    ->columns('name', 'email', 'age', 'status')
    ->values('John Doe', 'john@example.com', 25, 'active')
    ->run();

if ($insertId === null) {
    echo "❌ Insert failed: " . $queryBuilder->getError() . "\n";
} else {
    echo "✅ User created successfully! New user ID: {$insertId}\n";
}
echo "Generated SQL: " . $queryBuilder->insert('users')->columns('name', 'email', 'age', 'status')->__toString() . "\n\n";

// =============================================================================
// Example 2: INSERT with NULL values
// =============================================================================

echo "2. INSERT with NULL values:\n";
echo "---------------------------\n";

$productId = $queryBuilder->insert('products')
    ->columns('name', 'price', 'description', 'category_id', 'discount')
    ->values('Smartphone', 299.99, 'Latest model smartphone', 1, null) // NULL discount
    ->run();

if ($productId === null) {
    echo "❌ Product insert failed: " . $queryBuilder->getError() . "\n";
} else {
    echo "✅ Product created! Product ID: {$productId}\n";
}
echo "Note: NULL values are properly handled in parameter binding\n\n";

// =============================================================================
// Example 3: INSERT with Error Handling
// =============================================================================

echo "3. INSERT with Comprehensive Error Handling:\n";
echo "--------------------------------------------\n";

function createUser($queryBuilder, $userData) {
    // Validate required fields
    if (empty($userData['name']) || empty($userData['email'])) {
        echo "❌ Validation failed: Name and email are required\n";
        return null;
    }
    
    $insertId = $queryBuilder->insert('users')
        ->columns('name', 'email', 'password', 'created_at')
        ->values(
            $userData['name'],
            $userData['email'],
            password_hash($userData['password'], PASSWORD_DEFAULT),
            date('Y-m-d H:i:s')
        )
        ->run();
    
    if ($insertId === null) {
        $error = $queryBuilder->getError();
        if (strpos($error, 'Duplicate') !== false) {
            echo "❌ User with this email already exists\n";
        } else {
            echo "❌ Database error: {$error}\n";
        }
        return null;
    }
    
    echo "✅ User created successfully! ID: {$insertId}\n";
    return $insertId;
}

// Example usage
$userData = [
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'password' => 'secure123'
];

$newUserId = createUser($queryBuilder, $userData);
echo "\n";

// =============================================================================
// Example 4: INSERT with Transaction (Multiple Related Records)
// =============================================================================

echo "4. INSERT with Transaction (User + Profile + Settings):\n";
echo "------------------------------------------------------\n";

function createUserWithProfile($queryBuilder, $userData, $profileData) {
    // Begin transaction
    if (!$queryBuilder->beginTransaction()) {
        echo "❌ Failed to start transaction: " . $queryBuilder->getError() . "\n";
        return null;
    }
    
    try {
        // Insert user
        $userId = $queryBuilder->insert('users')
            ->columns('name', 'email', 'password', 'status', 'created_at')
            ->values(
                $userData['name'],
                $userData['email'],
                password_hash($userData['password'], PASSWORD_DEFAULT),
                'pending',
                date('Y-m-d H:i:s')
            )
            ->run();
            
        if ($userId === null) {
            throw new Exception('Failed to create user: ' . $queryBuilder->getError());
        }
        
        // Insert user profile
        $profileId = $queryBuilder->insert('user_profiles')
            ->columns('user_id', 'first_name', 'last_name', 'phone', 'bio')
            ->values(
                $userId,
                $profileData['first_name'] ?? null,
                $profileData['last_name'] ?? null,
                $profileData['phone'] ?? null,
                $profileData['bio'] ?? 'New user'
            )
            ->run();
            
        if ($profileId === null) {
            throw new Exception('Failed to create profile: ' . $queryBuilder->getError());
        }
        
        // Insert default user settings
        $settingsId = $queryBuilder->insert('user_settings')
            ->columns('user_id', 'notifications', 'theme', 'language')
            ->values($userId, 1, 'light', 'en')
            ->run();
            
        if ($settingsId === null) {
            throw new Exception('Failed to create settings: ' . $queryBuilder->getError());
        }
        
        // Commit transaction
        if (!$queryBuilder->commit()) {
            throw new Exception('Failed to commit transaction: ' . $queryBuilder->getError());
        }
        
        echo "✅ User, profile, and settings created successfully!\n";
        echo "   User ID: {$userId}\n";
        echo "   Profile ID: {$profileId}\n";
        echo "   Settings ID: {$settingsId}\n";
        
        return $userId;
        
    } catch (Exception $e) {
        // Rollback on any error
        if (!$queryBuilder->rollback()) {
            echo "❌ Rollback failed: " . $queryBuilder->getError() . "\n";
        }
        echo "❌ Transaction failed: " . $e->getMessage() . "\n";
        return null;
    }
}

// Example usage
$userData = [
    'name' => 'Bob Wilson',
    'email' => 'bob@example.com',
    'password' => 'secure456'
];

$profileData = [
    'first_name' => 'Bob',
    'last_name' => 'Wilson',
    'phone' => '+1234567890',
    'bio' => 'Software developer'
];

$result = createUserWithProfile($queryBuilder, $userData, $profileData);
echo "\n";

// =============================================================================
// Example 5: Batch INSERT Operations
// =============================================================================

echo "5. Batch INSERT Operations:\n";
echo "---------------------------\n";

function batchInsertProducts($queryBuilder, $products) {
    $results = ['success' => 0, 'errors' => 0, 'messages' => []];
    
    foreach ($products as $index => $product) {
        $productId = $queryBuilder->insert('products')
            ->columns('name', 'price', 'category_id', 'stock_quantity', 'created_at')
            ->values(
                $product['name'],
                $product['price'],
                $product['category_id'],
                $product['stock'] ?? 0,
                date('Y-m-d H:i:s')
            )
            ->run();
            
        if ($productId === null) {
            $results['errors']++;
            $results['messages'][] = "Product {$index}: " . $queryBuilder->getError();
        } else {
            $results['success']++;
            $results['messages'][] = "Product {$index}: Created with ID {$productId}";
        }
    }
    
    return $results;
}

// Example batch data
$products = [
    ['name' => 'Laptop', 'price' => 999.99, 'category_id' => 1, 'stock' => 10],
    ['name' => 'Mouse', 'price' => 29.99, 'category_id' => 1, 'stock' => 50],
    ['name' => 'Keyboard', 'price' => 79.99, 'category_id' => 1, 'stock' => 25],
    ['name' => 'Monitor', 'price' => 299.99, 'category_id' => 1, 'stock' => 15]
];

$batchResults = batchInsertProducts($queryBuilder, $products);

echo "Batch INSERT Results:\n";
echo "✅ Successful: {$batchResults['success']}\n";
echo "❌ Errors: {$batchResults['errors']}\n";
foreach ($batchResults['messages'] as $message) {
    echo "   - {$message}\n";
}
echo "\n";

// =============================================================================
// Example 6: INSERT with Dynamic Data
// =============================================================================

echo "6. INSERT with Dynamic Data (Form Processing):\n";
echo "----------------------------------------------\n";

function processOrderForm($queryBuilder, $formData) {
    // Simulate form data validation and processing
    $requiredFields = ['customer_id', 'total_amount'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            echo "❌ Missing required field: {$field}\n";
            return null;
        }
    }
    
    // Prepare insert data
    $orderData = [
        'customer_id' => (int)$formData['customer_id'],
        'total_amount' => (float)$formData['total_amount'],
        'status' => $formData['status'] ?? 'pending',
        'shipping_address' => $formData['shipping_address'] ?? null,
        'notes' => $formData['notes'] ?? null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $orderId = $queryBuilder->insert('orders')
        ->columns(...array_keys($orderData))
        ->values(...array_values($orderData))
        ->run();
    
    if ($orderId === null) {
        echo "❌ Order creation failed: " . $queryBuilder->getError() . "\n";
        return null;
    }
    
    echo "✅ Order created successfully! Order ID: {$orderId}\n";
    echo "   Customer: {$orderData['customer_id']}\n";
    echo "   Amount: \${$orderData['total_amount']}\n";
    echo "   Status: {$orderData['status']}\n";
    
    return $orderId;
}

// Simulate form data
$formData = [
    'customer_id' => 123,
    'total_amount' => 89.97,
    'status' => 'confirmed',
    'shipping_address' => '123 Main St, City, State 12345',
    'notes' => 'Leave at front door'
];

$orderId = processOrderForm($queryBuilder, $formData);
echo "\n";

// =============================================================================
// Example 7: INSERT with Validation and Error Handling
// =============================================================================

echo "7. INSERT with Enhanced Validation:\n";
echo "----------------------------------\n";

function createProductWithValidation($queryBuilder, $productData) {
    // Input validation
    $errors = [];
    
    if (empty($productData['name'])) {
        $errors[] = 'Product name is required';
    }
    
    if (!isset($productData['price']) || $productData['price'] <= 0) {
        $errors[] = 'Valid price is required';
    }
    
    if (empty($productData['category_id'])) {
        $errors[] = 'Category is required';
    }
    
    if (!empty($errors)) {
        echo "❌ Validation errors:\n";
        foreach ($errors as $error) {
            echo "   - {$error}\n";
        }
        return null;
    }
    
    // Data sanitization
    $cleanData = [
        'name' => trim($productData['name']),
        'price' => round((float)$productData['price'], 2),
        'description' => !empty($productData['description']) ? trim($productData['description']) : null,
        'category_id' => (int)$productData['category_id'],
        'stock_quantity' => isset($productData['stock']) ? max(0, (int)$productData['stock']) : 0,
        'is_active' => isset($productData['active']) ? (bool)$productData['active'] : true,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $productId = $queryBuilder->insert('products')
        ->columns(...array_keys($cleanData))
        ->values(...array_values($cleanData))
        ->run();
    
    if ($productId === null) {
        echo "❌ Product creation failed: " . $queryBuilder->getError() . "\n";
        return null;
    }
    
    echo "✅ Product created and validated successfully!\n";
    echo "   ID: {$productId}\n";
    echo "   Name: {$cleanData['name']}\n";
    echo "   Price: \${$cleanData['price']}\n";
    echo "   Stock: {$cleanData['stock_quantity']}\n";
    
    return $productId;
}

// Test with valid data
$validProduct = [
    'name' => 'Wireless Headphones',
    'price' => 149.99,
    'description' => 'High-quality wireless headphones with noise cancellation',
    'category_id' => 2,
    'stock' => 30,
    'active' => true
];

$validResult = createProductWithValidation($queryBuilder, $validProduct);

echo "\n";

// Test with invalid data
$invalidProduct = [
    'name' => '', // Empty name
    'price' => -10, // Invalid price
    // Missing category_id
];

$invalidResult = createProductWithValidation($queryBuilder, $invalidProduct);

echo "\n=== Summary of INSERT Features ===\n";
echo "✅ Basic INSERT with columns and values\n";
echo "✅ NULL value handling\n";
echo "✅ Comprehensive error handling\n";
echo "✅ Transaction support for related records\n";
echo "✅ Batch operations\n";
echo "✅ Dynamic data processing\n";
echo "✅ Input validation and sanitization\n";
echo "✅ Unified return pattern (ID|null)\n";
echo "✅ Automatic parameter binding (SQL injection safe)\n";
echo "✅ Enhanced error messages\n";