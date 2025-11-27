<?php
/**
 * System Page Router
 * 
 * Handles routing and POST request processing for system pages
 */
class SystemPageRouter
{
    private array $allowedPages;
    private string $templateDir;

    public function __construct(array $allowedPages, string $templateDir)
    {
        $this->allowedPages = $allowedPages;
        $this->templateDir = $templateDir;
    }

    /**
     * Handle POST requests (page routing, table selection, export/import, login)
     */
    public function handlePostRequests(): void
    {
        // Security check: Only allow in development mode
        if (($_ENV['APP_ENV'] ?? '') !== 'dev') {
            http_response_code(404);
            exit('Not Found');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Handle logout
        if (isset($_POST['logout'])) {
            unset($_SESSION['is_admin']);
            $_SESSION['page_to_show'] = 'login';
            return;
        }

        // Handle admin login
        if (isset($_POST['admin_login']) && isset($_POST['password'])) {
            $this->handleLogin($_POST['password']);
            return; // Login will redirect, so return early
        }

        // All other POST requests require admin authentication
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            $_SESSION['login_error'] = 'Please login to access this page';
            $_SESSION['page_to_show'] = 'login';
            return;
        }

        // Handle page routing
        if (isset($_POST['set_page'])) {
            $this->handlePageRouting($_POST['set_page']);
        }

        // Handle table selection
        if (isset($_POST['set_table'])) {
            $this->handleTableSelection($_POST['set_table']);
        }

        // Handle export
        if (isset($_POST['export_table']) && isset($_POST['export_format'])) {
            $this->handleExport($_POST['export_table'], $_POST['export_format']);
        }

        // Handle import
        if (isset($_POST['import_table']) && isset($_FILES['import_file'])) {
            $this->handleImport(
                $_POST['import_table'],
                $_POST['import_format'] ?? '',
                $_FILES['import_file']
            );
        }
    }

    /**
     * Handle admin login
     */
    private function handleLogin(string $password): void
    {
        try {
            // Load environment variables
            \Gemvc\Helper\ProjectHelper::loadEnv();
            
            // Get admin password from .env (stored in plain text for simplicity)
            $adminPassword = trim($_ENV['ADMIN_PASSWORD'] ?? '');
            
            // Check if admin password is set
            if (empty($adminPassword)) {
                $_SESSION['login_error'] = 'Admin password not configured. Please run: php vendor/bin/gemvc admin:setpassword';
                $_SESSION['page_to_show'] = 'login';
                return;
            }

            // Simple plain text comparison (acceptable for dev-only system pages)
            if (trim($password) === $adminPassword) {
                // Password correct - set admin session
                $_SESSION['is_admin'] = true;
                unset($_SESSION['login_error']);
                
                // Redirect to developer-welcome page
                $_SESSION['page_to_show'] = 'developer-welcome';
            } else {
                // Password incorrect
                $_SESSION['login_error'] = 'Invalid password. Please try again.';
                $_SESSION['page_to_show'] = 'login';
            }
        } catch (\Exception $e) {
            $_SESSION['login_error'] = 'Login error: ' . $e->getMessage();
            $_SESSION['page_to_show'] = 'login';
        }
    }

    /**
     * Handle page routing
     */
    private function handlePageRouting(string $requestedPage): void
    {
        $requestedPage = strtolower(trim($requestedPage));
        if (in_array($requestedPage, $this->allowedPages, true)) {
            $_SESSION['page_to_show'] = $requestedPage;
        }
    }

    /**
     * Handle table selection
     */
    private function handleTableSelection(string $tableName): void
    {
        $_SESSION['selected_table'] = trim($tableName);
    }

    /**
     * Handle export request
     */
    private function handleExport(string $tableName, string $format): void
    {
        $exportTable = trim($tableName);
        $exportFormat = trim($format);
        
        try {
            $dbManager = \Gemvc\Database\DatabaseManagerFactory::getManager();
            $connection = $dbManager->getConnection();
            if ($connection !== null) {
                /** @phpstan-var DatabaseExporter */
                $exporter = new DatabaseExporter($connection);
                
                if ($exportFormat === 'csv') {
                    $exporter->exportCsv($exportTable);
                } elseif ($exportFormat === 'sql') {
                    $exporter->exportSql($exportTable);
                }
            }
        } catch (\Exception $e) {
            $_SESSION['export_error'] = $e->getMessage();
        }
    }

    /**
     * Handle import request
     */
    private function handleImport(string $tableName, string $format, array $file): void
    {
        $importTable = trim($tableName);
        $importFormat = trim($format);
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            try {
                $dbManager = \Gemvc\Database\DatabaseManagerFactory::getManager();
                $connection = $dbManager->getConnection();
                if ($connection !== null) {
                    /** @phpstan-var DatabaseImporter */
                    $importer = new DatabaseImporter($connection);
                    
                    if ($importFormat === 'csv') {
                        $imported = $importer->importCsv($importTable, $file['tmp_name']);
                        $_SESSION['import_success'] = "Successfully imported $imported rows into $importTable";
                    } elseif ($importFormat === 'sql') {
                        $importer->importSql($file['tmp_name']);
                        $_SESSION['import_success'] = "Successfully executed SQL file for $importTable";
                    }
                }
            } catch (\Exception $e) {
                $_SESSION['import_error'] = $e->getMessage();
            }
        } else {
            $_SESSION['import_error'] = 'File upload error: ' . $file['error'];
        }
    }

    /**
     * Get the current page to show
     */
    public function getCurrentPage(): string
    {
        // Check if user is authenticated
        $isAuthenticated = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
        
        // If not authenticated, show login page (unless already on login)
        if (!$isAuthenticated) {
            $currentPage = $_SESSION['page_to_show'] ?? 'login';
            if ($currentPage === 'login') {
                return 'login';
            }
            // If trying to access other pages without auth, redirect to login
            $_SESSION['page_to_show'] = 'login';
            return 'login';
        }

        // User is authenticated - get requested page
        $pageToShow = $_SESSION['page_to_show'] ?? 'developer-welcome';
        $pageToShow = strtolower($pageToShow);
        
        // Don't allow login page if already authenticated
        if ($pageToShow === 'login') {
            $pageToShow = 'developer-welcome';
        }
        
        // Validate page is in whitelist
        if (!in_array($pageToShow, $this->allowedPages, true)) {
            $pageToShow = 'developer-welcome';
        }
        
        return $pageToShow;
    }
}

