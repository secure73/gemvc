<?php
/**
 * Admin Login Page
 * 
 * This page allows developers to authenticate before accessing system pages.
 * Only accessible in development mode.
 * 
 * @var string $baseUrl The base URL of the application
 * @var string $templateDir The directory path where this template is located
 * @var string|null $errorMessage Error message to display (if any)
 */

// Security check: Defense-in-depth
if (($_ENV['APP_ENV'] ?? '') !== 'dev') {
    http_response_code(404);
    exit('Not Found');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errorMessage = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// Check if ADMIN_PASSWORD is set and not empty
$adminPasswordSet = !empty(trim($_ENV['ADMIN_PASSWORD'] ?? ''));

// Prepare variables for login page
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$port = $_SERVER['SERVER_PORT'] ?? '';
$portDisplay = ($port && $port !== '80' && $port !== '443') ? ':' . $port : '';
$baseUrl = $protocol . '://' . $host . $portDisplay;

// Load GEMVC logo
$gemvcLogoUrl = null;
$gemvcLogoPath = $templateDir . DIRECTORY_SEPARATOR . 'gemvc_logo.svg';
if (file_exists($gemvcLogoPath)) {
    $logoContent = file_get_contents($gemvcLogoPath);
    if ($logoContent !== false) {
        $gemvcLogoUrl = 'data:image/svg+xml;base64,' . base64_encode($logoContent);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - GEMVC Framework</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                    },
                    colors: {
                        gemvc: {
                            green: '#10b981',
                            'green-dark': '#059669',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gemvc-green to-gemvc-green-dark font-sans font-normal">
<div class="flex items-center justify-center min-h-screen p-5">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-10">
        <div class="text-center mb-8">
            <?php if (isset($gemvcLogoUrl) && $gemvcLogoUrl): ?>
                <img src="<?php echo htmlspecialchars($gemvcLogoUrl); ?>" alt="GEMVC Logo" class="h-16 mx-auto mb-4">
            <?php endif; ?>
            <h1 class="text-3xl font-bold text-gemvc-green mb-2 tracking-tight">Admin Login</h1>
            <p class="text-gray-600">Enter your admin password to access system pages</p>
        </div>

        <?php if ($errorMessage): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded mb-6">
                <p class="text-red-800 text-sm"><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="admin_login" value="1">
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Admin Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autofocus
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gemvc-green focus:border-transparent transition-colors"
                    placeholder="Enter your admin password"
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-gemvc-green hover:bg-gemvc-green-dark text-white font-medium py-3 px-4 rounded-lg transition-colors shadow-md hover:shadow-lg"
            >
                Login
            </button>
        </form>

        <?php if (!$adminPasswordSet): ?>
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Set admin password using:<br>
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">php vendor/bin/gemvc admin:setpassword</code>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

