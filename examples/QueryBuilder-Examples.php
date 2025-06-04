<?php

/**
 * Practical QueryBuilder Examples
 * 
 * Real-world usage scenarios demonstrating the enhanced QueryBuilder features
 */

require_once 'src/database/QueryBuilder.php';
use Gemvc\Database\QueryBuilder;

// =============================================================================
// Example 1: E-commerce Product Search with Filters
// =============================================================================

class ProductService {
    private QueryBuilder $queryBuilder;
    
    public function __construct() {
        $this->queryBuilder = new QueryBuilder();
    }
    
    /**
     * Search products with multiple filters and pagination
     */
    public function searchProducts(array $filters, int $page = 1, int $perPage = 20): array {
        $query = $this->queryBuilder->select('p.*', 'c.name as category_name', 'b.name as brand_name')
            ->from('products', 'p')
            ->leftJoin('categories c ON p.category_id = c.id')
            ->leftJoin('brands b ON p.brand_id = b.id')
            ->whereEqual('p.status', 'active');
        
        // Apply filters dynamically
        if (!empty($filters['category'])) {
            $query->whereEqual('p.category_id', $filters['category']);
        }
        
        if (!empty($filters['brand'])) {
            $query->whereIn('p.brand_id', $filters['brand']);
        }
        
        if (!empty($filters['price_min'])) {
            $query->whereBiggerEqual('p.price', $filters['price_min']);
        }
        
        if (!empty($filters['price_max'])) {
            $query->whereLessEqual('p.price', $filters['price_max']);
        }
        
        if (!empty($filters['search'])) {
            $query->whereLike('p.name', $filters['search']);
        }
        
        if (!empty($filters['in_stock'])) {
            $query->whereBigger('p.stock_quantity', 0);
        }
        
        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? false;
        $query->orderBy($sortField, $sortDir);
        
        // Apply pagination
        $query->paginate($page, $perPage);
        
        $results = $query->run();
        
        if ($results === null) {
            error_log('Product search failed: ' . $this->queryBuilder->getError());
            return [];
        }
        
        return $results;
    }
    
    /**
     * Get product statistics
     */
    public function getProductStats(): array {
        $stats = [];
        
        // Total products by category
        $categoryStats = $this->queryBuilder->select('c.name', 'COUNT(p.id) as product_count')
            ->from('products', 'p')
            ->leftJoin('categories c ON p.category_id = c.id')
            ->whereEqual('p.status', 'active')
            ->run();
            
        if ($categoryStats !== null) {
            $stats['by_category'] = $categoryStats;
        }
        
        // Price ranges
        $priceStats = $this->queryBuilder->select('MIN(price) as min_price', 'MAX(price) as max_price', 'AVG(price) as avg_price')
            ->from('products')
            ->whereEqual('status', 'active')
            ->run();
            
        if ($priceStats !== null && !empty($priceStats)) {
            $stats['price_stats'] = $priceStats[0];
        }
        
        return $stats;
    }
}

// =============================================================================
// Example 2: User Management with Role-Based Access
// =============================================================================

class UserService {
    private QueryBuilder $queryBuilder;
    
    public function __construct() {
        $this->queryBuilder = new QueryBuilder();
    }
    
    /**
     * Create a new user with profile
     */
    public function createUserWithProfile(array $userData, array $profileData): ?int {
        if (!$this->queryBuilder->beginTransaction()) {
            return null;
        }
        
        try {
            // Create user
            $userId = $this->queryBuilder->insert('users')
                ->columns('name', 'email', 'password', 'status')
                ->values(
                    $userData['name'],
                    $userData['email'],
                    password_hash($userData['password'], PASSWORD_DEFAULT),
                    'active'
                )
                ->run();
                
            if ($userId === null) {
                throw new Exception('Failed to create user');
            }
            
            // Create user profile
            $profileId = $this->queryBuilder->insert('user_profiles')
                ->columns('user_id', 'first_name', 'last_name', 'phone', 'bio')
                ->values(
                    $userId,
                    $profileData['first_name'] ?? null,
                    $profileData['last_name'] ?? null,
                    $profileData['phone'] ?? null,
                    $profileData['bio'] ?? null
                )
                ->run();
                
            if ($profileId === null) {
                throw new Exception('Failed to create user profile');
            }
            
            // Assign default role
            $roleAssignment = $this->queryBuilder->insert('user_roles')
                ->columns('user_id', 'role_id')
                ->values($userId, 3) // Default user role
                ->run();
                
            if ($roleAssignment === null) {
                throw new Exception('Failed to assign user role');
            }
            
            if (!$this->queryBuilder->commit()) {
                throw new Exception('Failed to commit transaction');
            }
            
            return $userId;
            
        } catch (Exception $e) {
            $this->queryBuilder->rollback();
            error_log('User creation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get users with their roles and recent activity
     */
    public function getUsersWithDetails(array $filters = []): array {
        $query = $this->queryBuilder->select(
                'u.id',
                'u.name',
                'u.email',
                'u.status',
                'u.last_login',
                'up.first_name',
                'up.last_name',
                'r.name as role_name'
            )
            ->from('users', 'u')
            ->leftJoin('user_profiles up ON u.id = up.user_id')
            ->leftJoin('user_roles ur ON u.id = ur.user_id')
            ->leftJoin('roles r ON ur.role_id = r.id');
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query->whereEqual('u.status', $filters['status']);
        }
        
        if (!empty($filters['role'])) {
            $query->whereEqual('r.name', $filters['role']);
        }
        
        if (!empty($filters['recent_login_days'])) {
            $cutoffDate = date('Y-m-d', strtotime("-{$filters['recent_login_days']} days"));
            $query->whereBigger('u.last_login', $cutoffDate);
        }
        
        return $query->orderBy('u.created_at', true)->run() ?? [];
    }
    
    /**
     * Update user last login
     */
    public function updateLastLogin(int $userId): bool {
        $result = $this->queryBuilder->update('users')
            ->set('last_login', date('Y-m-d H:i:s'))
            ->set('login_count', 'login_count + 1') // SQL expression
            ->whereEqual('id', $userId)
            ->run();
            
        return $result !== null && $result > 0;
    }
}

// =============================================================================
// Example 3: Analytics and Reporting
// =============================================================================

class AnalyticsService {
    private QueryBuilder $queryBuilder;
    
    public function __construct() {
        $this->queryBuilder = new QueryBuilder();
    }
    
    /**
     * Get sales report for a date range
     */
    public function getSalesReport(string $startDate, string $endDate): array {
        // Daily sales summary
        $dailySales = $this->queryBuilder->select(
                'DATE(created_at) as sale_date',
                'COUNT(*) as order_count',
                'SUM(total_amount) as total_sales',
                'AVG(total_amount) as avg_order_value'
            )
            ->from('orders')
            ->whereEqual('status', 'completed')
            ->whereBetween('created_at', $startDate, $endDate)
            ->run() ?? [];
        
        // Top products
        $topProducts = $this->queryBuilder->select(
                'p.name',
                'SUM(oi.quantity) as total_sold',
                'SUM(oi.quantity * oi.price) as revenue'
            )
            ->from('order_items', 'oi')
            ->leftJoin('products p ON oi.product_id = p.id')
            ->leftJoin('orders o ON oi.order_id = o.id')
            ->whereEqual('o.status', 'completed')
            ->whereBetween('o.created_at', $startDate, $endDate)
            ->orderBy('total_sold', true)
            ->limit(10)
            ->run() ?? [];
        
        // Customer segments
        $customerSegments = $this->queryBuilder->select(
                'CASE 
                    WHEN total_orders >= 10 THEN "VIP"
                    WHEN total_orders >= 5 THEN "Regular"
                    ELSE "New"
                END as segment',
                'COUNT(*) as customer_count'
            )
            ->from('(
                SELECT customer_id, COUNT(*) as total_orders
                FROM orders
                WHERE status = "completed"
                GROUP BY customer_id
            ) as customer_orders')
            ->run() ?? [];
        
        return [
            'daily_sales' => $dailySales,
            'top_products' => $topProducts,
            'customer_segments' => $customerSegments
        ];
    }
    
    /**
     * Get real-time dashboard metrics
     */
    public function getDashboardMetrics(): array {
        $metrics = [];
        
        // Today's orders
        $todayOrders = $this->queryBuilder->select('COUNT(*) as count', 'SUM(total_amount) as revenue')
            ->from('orders')
            ->whereEqual('DATE(created_at)', date('Y-m-d'))
            ->run();
            
        if ($todayOrders && !empty($todayOrders)) {
            $metrics['today'] = $todayOrders[0];
        }
        
        // Pending orders
        $pendingOrders = $this->queryBuilder->select('COUNT(*) as count')
            ->from('orders')
            ->whereEqual('status', 'pending')
            ->run();
            
        if ($pendingOrders && !empty($pendingOrders)) {
            $metrics['pending_orders'] = $pendingOrders[0]['count'];
        }
        
        // Low stock products
        $lowStockProducts = $this->queryBuilder->select('COUNT(*) as count')
            ->from('products')
            ->whereLess('stock_quantity', 10)
            ->whereEqual('status', 'active')
            ->run();
            
        if ($lowStockProducts && !empty($lowStockProducts)) {
            $metrics['low_stock'] = $lowStockProducts[0]['count'];
        }
        
        return $metrics;
    }
}

// =============================================================================
// Example 4: Data Migration and Batch Operations
// =============================================================================

class DataMigrationService {
    private QueryBuilder $queryBuilder;
    
    public function __construct() {
        $this->queryBuilder = new QueryBuilder();
    }
    
    /**
     * Migrate old user data to new format
     */
    public function migrateUserData(): array {
        $results = ['success' => 0, 'errors' => 0, 'messages' => []];
        
        // Get users needing migration
        $oldUsers = $this->queryBuilder->select('*')
            ->from('old_users')
            ->whereNull('migrated_at')
            ->limit(100) // Process in batches
            ->run();
            
        if ($oldUsers === null) {
            $results['messages'][] = 'Failed to fetch old users: ' . $this->queryBuilder->getError();
            return $results;
        }
        
        foreach ($oldUsers as $oldUser) {
            if (!$this->queryBuilder->beginTransaction()) {
                $results['errors']++;
                continue;
            }
            
            try {
                // Create new user record
                $newUserId = $this->queryBuilder->insert('users')
                    ->columns('name', 'email', 'legacy_id', 'created_at')
                    ->values(
                        $oldUser['full_name'],
                        $oldUser['email_address'],
                        $oldUser['id'],
                        $oldUser['registration_date']
                    )
                    ->run();
                    
                if ($newUserId === null) {
                    throw new Exception('Failed to create new user');
                }
                
                // Mark as migrated
                $updateResult = $this->queryBuilder->update('old_users')
                    ->set('migrated_at', date('Y-m-d H:i:s'))
                    ->set('new_user_id', $newUserId)
                    ->whereEqual('id', $oldUser['id'])
                    ->run();
                    
                if ($updateResult === null) {
                    throw new Exception('Failed to mark as migrated');
                }
                
                $this->queryBuilder->commit();
                $results['success']++;
                
            } catch (Exception $e) {
                $this->queryBuilder->rollback();
                $results['errors']++;
                $results['messages'][] = "Failed to migrate user {$oldUser['id']}: " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Clean up old data based on retention policy
     */
    public function cleanupOldData(int $retentionDays = 90): array {
        $cutoffDate = date('Y-m-d', strtotime("-{$retentionDays} days"));
        $results = [];
        
        // Delete old logs
        $deletedLogs = $this->queryBuilder->delete('system_logs')
            ->whereLess('created_at', $cutoffDate)
            ->whereEqual('level', 'debug')
            ->run();
            
        $results['deleted_logs'] = $deletedLogs ?? 0;
        
        // Archive old orders
        $oldOrders = $this->queryBuilder->select('*')
            ->from('orders')
            ->whereLess('created_at', $cutoffDate)
            ->whereEqual('status', 'completed')
            ->run();
            
        if ($oldOrders !== null) {
            // Move to archive table (simplified)
            foreach ($oldOrders as $order) {
                $this->queryBuilder->insert('orders_archive')
                    ->columns('original_id', 'customer_id', 'total_amount', 'created_at')
                    ->values($order['id'], $order['customer_id'], $order['total_amount'], $order['created_at'])
                    ->run();
            }
            
            // Delete from main table
            $deletedOrders = $this->queryBuilder->delete('orders')
                ->whereLess('created_at', $cutoffDate)
                ->whereEqual('status', 'completed')
                ->run();
                
            $results['archived_orders'] = $deletedOrders ?? 0;
        }
        
        return $results;
    }
}

// =============================================================================
// Usage Examples
// =============================================================================

echo "Enhanced QueryBuilder - Practical Examples\n";
echo "==========================================\n\n";

// Example usage (these would normally be called from controllers/services)

// 1. Product search
$productService = new ProductService();
$searchFilters = [
    'category' => 1,
    'price_min' => 50,
    'price_max' => 500,
    'search' => 'smartphone',
    'in_stock' => true,
    'sort' => 'price',
    'sort_dir' => false
];

echo "1. Product Search Example:\n";
echo "Filters: " . json_encode($searchFilters) . "\n";
echo "This would return paginated, filtered product results\n\n";

// 2. User creation
$userService = new UserService();
echo "2. User Creation Example:\n";
echo "Creating user with profile in a transaction\n";
echo "Includes automatic rollback on any failure\n\n";

// 3. Analytics
$analyticsService = new AnalyticsService();
echo "3. Analytics Example:\n";
echo "Generating sales reports with complex aggregations\n";
echo "Multiple queries for comprehensive dashboard data\n\n";

// 4. Data migration
$migrationService = new DataMigrationService();
echo "4. Data Migration Example:\n";
echo "Batch processing with error handling\n";
echo "Transaction-safe data transformation\n\n";

echo "✅ All examples demonstrate:\n";
echo "  - Unified error handling (result|null pattern)\n";
echo "  - Safe parameter binding\n";
echo "  - Transaction management\n";
echo "  - Complex WHERE conditions\n";
echo "  - Modern pagination\n";
echo "  - Real-world SQL patterns\n";
echo "  - Production-ready error handling\n"; 