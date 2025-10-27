<?php

namespace Gemvc\Database;

use PDO;
use Gemvc\Core\WebserverDetector;

/**
 * Database Manager Factory with Performance Optimization
 * 
 * This factory automatically chooses the appropriate database manager
 * implementation based on the current web server environment:
 * 
 * - OpenSwoole: Uses SwooleDatabaseManager (with connection pooling)
 * - Apache/Nginx PHP-FPM: Uses PDO-based manager (configurable)
 *   - Simple: SimplePdoDatabaseManager (default, no persistence)
 *   - Enhanced: EnhancedPdoDatabaseManager (if DB_ENHANCED_CONNECTION=1)
 * 
 * Environment Variable Control:
 * - DB_ENHANCED_CONNECTION=1: Use EnhancedPdoDatabaseManager with persistent connections
 * - DB_ENHANCED_CONNECTION=0 (default): Use SimplePdoDatabaseManager with simple connections
 * 
 * Performance optimizations:
 * - Environment detection cached after first run
 * - Singleton pattern prevents repeated detection
 * - Fast-path checks prioritized
 * - Minimal function calls
 * 
 * The factory detects the environment and returns the appropriate implementation.
 */
class DatabaseManagerFactory
{
    /** @var DatabaseManagerInterface|null Singleton instance */
    private static ?DatabaseManagerInterface $instance = null;

    /** @var string|null Cached environment detection result */
    private static ?string $cachedEnvironment = null;

    /** @var bool Whether detection has been performed */
    private static bool $detectionPerformed = false;

    /**
     * Get the appropriate database manager for the current environment
     * 
     * @return DatabaseManagerInterface The database manager instance
     */
    public static function getManager(): DatabaseManagerInterface
    {
        if (self::$instance === null) {
            self::$instance = self::createManager();
        }
        return self::$instance;
    }

    /**
     * Create the appropriate database manager based on cached environment
     * 
     * @return DatabaseManagerInterface The database manager instance
     */
    private static function createManager(): DatabaseManagerInterface
    {
        $environment = self::getCachedEnvironment();
        
        switch ($environment) {
            case 'swoole':
                return self::createSwooleManager();
                
            case 'apache':
            case 'nginx':
            default:
                return self::createPdoManager();
        }
    }

    /**
     * Get cached environment detection result
     * 
     * @return string The detected environment ('swoole', 'apache', 'nginx')
     */
    private static function getCachedEnvironment(): string
    {
        if (!self::$detectionPerformed) {
            self::$cachedEnvironment = WebserverDetector::get();
            self::$detectionPerformed = true;
        }
        
        return self::$cachedEnvironment ?? 'apache';
    }


    /**
     * Create Swoole database manager
     * 
     * @return DatabaseManagerInterface The Swoole database manager
     */
    private static function createSwooleManager(): DatabaseManagerInterface
    {
        // Create a wrapper that implements our interface
        return new SwooleDatabaseManagerAdapter();
    }

    /**
     * Create a PDO database manager instance based on configuration
     * 
     * Checks DB_ENHANCED_CONNECTION environment variable:
     * - If set to '1' or 'true': Uses EnhancedPdoDatabaseManager with persistent connections
     * - Otherwise: Uses SimplePdoDatabaseManager with simple connections
     * 
     * @return DatabaseManagerInterface The appropriate PDO database manager instance
     */
    private static function createPdoManager(): DatabaseManagerInterface
    {
        $useEnhanced = $_ENV['DB_ENHANCED_CONNECTION'] ?? '0';
        
        // Check if enhanced connections are enabled
        if ($useEnhanced === '1' || $useEnhanced === 'true' || $useEnhanced === 'yes') {
            return EnhancedPdoDatabaseManager::getInstance(true); // Use persistent connections
        }
        
        return SimplePdoDatabaseManager::getInstance(); // Use simple connections
    }

    /**
     * Reset the singleton instance and cache (useful for testing)
     * 
     * @return void
     */
    public static function resetInstance(): void
    {
        if (self::$instance !== null) {
            // Call reset on the underlying manager if it has one
            if (method_exists(self::$instance, 'resetInstance')) {
                self::$instance->resetInstance();
            }
            self::$instance = null;
        }
        self::$cachedEnvironment = null;
        self::$detectionPerformed = false;
    }

    /**
     * Get information about the current database manager
     * 
     * @return array<string, mixed> Manager information
     */
    public static function getManagerInfo(): array
    {
        $manager = self::getManager();
        $environment = self::getCachedEnvironment();
        
        $info = [
            'environment' => $environment,
            'manager_class' => get_class($manager),
            'pool_stats' => $manager->getPoolStats(),
            'initialized' => $manager->isInitialized(),
            'has_error' => $manager->getError() !== null,
            'error' => $manager->getError(),
            'detection_cached' => self::$detectionPerformed,
            'performance_mode' => 'optimized'
        ];
        
        // Add PDO-specific configuration info
        if ($environment === 'apache' || $environment === 'nginx') {
            $useEnhanced = $_ENV['DB_ENHANCED_CONNECTION'] ?? '0';
            $info['pdo_config'] = [
                'enhanced_connection' => $useEnhanced,
                'persistent_enabled' => ($useEnhanced === '1' || $useEnhanced === 'true' || $useEnhanced === 'yes'),
                'implementation' => ($useEnhanced === '1' || $useEnhanced === 'true' || $useEnhanced === 'yes') 
                    ? 'EnhancedPdoDatabaseManager' 
                    : 'SimplePdoDatabaseManager'
            ];
        }
        
        return $info;
    }

    /**
     * Force environment detection (bypasses cache)
     * 
     * @return string The detected environment
     */
    public static function forceDetection(): string
    {
        self::$detectionPerformed = false;
        self::$cachedEnvironment = WebserverDetector::forceRefresh();
        self::$detectionPerformed = true;
        return self::$cachedEnvironment;
    }

    /**
     * Get performance metrics for detection
     * 
     * @return array<string, mixed> Performance metrics
     */
    public static function getPerformanceMetrics(): array
    {
        $metrics = WebserverDetector::getMetrics();
        $metrics['detection_cached'] = self::$detectionPerformed;
        return $metrics;
    }
}


