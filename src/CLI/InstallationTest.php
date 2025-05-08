<?php

namespace Gemvc\CLI;

class InstallationTest {
    public static function verify(): bool {
        // Verify autoloader
        if (!class_exists('Gemvc\CLI\Command')) {
            throw new \RuntimeException('CLI autoloader not working');
        }

        // Verify command class
        if (!class_exists('Gemvc\CLI\Commands\CreateService')) {
            throw new \RuntimeException('CreateService command not found');
        }

        // Verify directory permissions
        $dirs = ['app/api', 'app/controller', 'app/model', 'app/table'];
        foreach ($dirs as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                throw new \RuntimeException("Cannot create directory: $dir");
            }
        }

        return true;
    }
} 