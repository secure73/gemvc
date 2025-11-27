<?php
/**
 * Central System Pages Controller
 * 
 * This is the single entry point for all system pages in development mode.
 * It manages session-based page routing and loads the appropriate page.
 * 
 * @var string $baseUrl The base URL of the application
 * @var string $apiBaseUrl The base URL for API endpoints
 * @var string $webserverType The detected webserver type ('apache', 'nginx', 'swoole')
 * @var string $webserverName The detected webserver name (Apache, Nginx, or OpenSwoole)
 * @var string $templateDir The directory path where this template is located
 */

// Security check: Only allow system pages in development mode
// Load environment variables first to check APP_ENV
try {
    \Gemvc\Helper\ProjectHelper::loadEnv();
} catch (\Exception $e) {
    // If .env can't be loaded, deny access for security
    http_response_code(404);
    exit('Not Found');
}

// Double check: Only proceed if APP_ENV is 'dev'
if (($_ENV['APP_ENV'] ?? '') !== 'dev') {
    http_response_code(404);
    exit('Not Found');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get template directory (this file is in system_pages directory)
$templateDir = __DIR__;

// Load required classes
/** @var SystemPageRouter */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'SystemPageRouter.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'DatabaseExporter.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'DatabaseImporter.php';

// Whitelist of allowed system pages (security)
$allowedPages = ['developer-welcome', 'status', 'info', 'logs', 'database', 'login'];

// Initialize router and handle POST requests
$router = new SystemPageRouter($allowedPages, $templateDir);
$router->handlePostRequests();

// Get current page to show
$pageToShow = $router->getCurrentPage();

// Prepare common variables for pages
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$port = $_SERVER['SERVER_PORT'] ?? '';
$portDisplay = ($port && $port !== '80' && $port !== '443') ? ':' . $port : '';
$baseUrl = $protocol . '://' . $host . $portDisplay;
$apiBaseUrl = rtrim($baseUrl, '/') . '/api';

$webserverType = \Gemvc\Core\WebserverDetector::get();
$webserverName = match($webserverType) {
    'swoole' => 'OpenSwoole',
    'apache' => 'Apache',
    'nginx' => 'Nginx',
    default => ucfirst($webserverType)
};


// Load logos (common for all pages)
$gemvcLogoUrl = null;
$gemvcLogoPath = $templateDir . DIRECTORY_SEPARATOR . 'gemvc_logo.svg';
if (file_exists($gemvcLogoPath)) {
    $logoContent = file_get_contents($gemvcLogoPath);
    if ($logoContent !== false) {
        $gemvcLogoUrl = 'data:image/svg+xml;base64,' . base64_encode($logoContent);
    }
}

$webserverLogoUrl = null;
if ($webserverType === 'apache') {
    $apacheLogoPath = $templateDir . DIRECTORY_SEPARATOR . 'apache.svg';
    if (file_exists($apacheLogoPath)) {
        $apacheLogoContent = file_get_contents($apacheLogoPath);
        if ($apacheLogoContent !== false) {
            $webserverLogoUrl = 'data:image/svg+xml;base64,' . base64_encode($apacheLogoContent);
        }
    }
} elseif ($webserverType === 'nginx') {
    $nginxLogoPath = $templateDir . DIRECTORY_SEPARATOR . 'nginx.svg';
    if (file_exists($nginxLogoPath)) {
        $nginxLogoContent = file_get_contents($nginxLogoPath);
        if ($nginxLogoContent !== false) {
            $webserverLogoUrl = 'data:image/svg+xml;base64,' . base64_encode($nginxLogoContent);
        }
    }
} elseif ($webserverType === 'swoole') {
    $swooleLogoPath = $templateDir . DIRECTORY_SEPARATOR . 'swoole.svg';
    if (file_exists($swooleLogoPath)) {
        $swooleLogoContent = file_get_contents($swooleLogoPath);
        if ($swooleLogoContent !== false) {
            $webserverLogoUrl = 'data:image/svg+xml;base64,' . base64_encode($swooleLogoContent);
        }
    }
}

// Determine page title
$pageTitle = match($pageToShow) {
    'database' => 'Database Management - GEMVC Framework',
    'login' => 'Admin Login - GEMVC Framework',
    default => 'GEMVC Framework - Development Server'
};

// Path to partials directory
$partialsDir = $templateDir . DIRECTORY_SEPARATOR . 'partials';

// If login page, don't show navbar/footer
if ($pageToShow === 'login') {
    // Load login page directly (full page, no navbar/footer)
    // Pass templateDir to login page
    $loginPath = $templateDir . DIRECTORY_SEPARATOR . 'login.php';
    if (file_exists($loginPath)) {
        // $templateDir is already defined above
        require_once $loginPath;
        exit;
    }
}
?>
<?php require_once $partialsDir . DIRECTORY_SEPARATOR . 'head.php'; ?>
<?php require_once $partialsDir . DIRECTORY_SEPARATOR . 'navbar.php'; ?>

<div class="flex items-center justify-center p-5">
    <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full p-10">
        <?php
        // Load the requested page content
        $pagePath = $templateDir . DIRECTORY_SEPARATOR . $pageToShow . '.php';
        if (file_exists($pagePath)) {
            require_once $pagePath;
        } else {
            // Fallback to developer-welcome if page not found
            $fallbackPath = $templateDir . DIRECTORY_SEPARATOR . 'developer-welcome.php';
            if (file_exists($fallbackPath)) {
                require_once $fallbackPath;
            }
        }
        ?>
    </div>
</div>

<?php require_once $partialsDir . DIRECTORY_SEPARATOR . 'footer.php'; ?>
