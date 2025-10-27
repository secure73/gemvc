<?php

namespace Gemvc\Core;

/**
 * Hot Reload Manager
 * 
 * Handles file change detection and server reloading in development mode
 */
class HotReloadManager
{
    private int $lastCheck;
    private string $lastFileHash;
    private int $checkInterval;
    private int $minReloadInterval;

    public function __construct()
    {
        $this->lastCheck = time();
        $this->lastFileHash = '';
        $this->checkInterval = 15000; // 15 seconds
        $this->minReloadInterval = 10; // 10 seconds minimum between reloads
    }

    /**
     * Start hot reload monitoring
     */
    public function startHotReload(object $server): void
    {
        $this->lastFileHash = $this->getFileHash();
        
        // @phpstan-ignore-next-line
        $server->tick($this->checkInterval, function () use ($server) {
            $this->checkForChanges($server);
        });
    }

    /**
     * Check for file changes and reload if necessary
     */
    private function checkForChanges(object $server): void
    {
        $currentTime = time();
        $currentFileHash = $this->getFileHash();
        
        // Only reload if files have changed AND enough time has passed
        if ($currentFileHash !== $this->lastFileHash && 
            ($currentTime - $this->lastCheck) >= $this->minReloadInterval) {
            
            $this->lastCheck = $currentTime;
            $this->lastFileHash = $currentFileHash;
            
            echo "File changes detected, reloading server...\n";
            // @phpstan-ignore-next-line
            $server->reload();
        }
    }

    /**
     * Get hash of all PHP files in the project
     */
    private function getFileHash(): string
    {
        $files = [];
        $dirs = ['app', 'vendor/gemvc/library/src'];
        
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($iterator as $file) {
                    if (is_object($file) && method_exists($file, 'isFile') && method_exists($file, 'getExtension') && 
                        method_exists($file, 'getPathname') && method_exists($file, 'getMTime') &&
                        $file->isFile() && $file->getExtension() === 'php') {
                        $files[] = $file->getPathname() . ':' . $file->getMTime();
                    }
                }
            }
        }
        
        return md5(implode('|', $files));
    }

    /**
     * Set the check interval for file changes
     */
    public function setCheckInterval(int $interval): void
    {
        $this->checkInterval = $interval;
    }

    /**
     * Set the minimum interval between reloads
     */
    public function setMinReloadInterval(int $interval): void
    {
        $this->minReloadInterval = $interval;
    }

    /**
     * Add a directory to monitor for changes
     */
    public function addWatchDirectory(string $directory): void
    {
        // This could be extended to support dynamic directory watching
        // For now, directories are hardcoded in getFileHash()
    }

    /**
     * Force a reload (useful for testing)
     */
    public function forceReload(object $server): void
    {
        echo "Forcing server reload...\n";
        // @phpstan-ignore-next-line
        $server->reload();
    }
}
