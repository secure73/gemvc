<?php
/**
 * Developer Welcome Page Fallback Template
 * 
 * This is a minimal fallback template shown if the main developer-welcome.php
 * template is not found. This should rarely be needed.
 * 
 * @var string $baseUrl The base URL of the application
 * @var string $apiBaseUrl The base URL for API endpoints
 */

// Security check: Defense-in-depth (already protected by index.php, but extra safety)
if (($_ENV['APP_ENV'] ?? '') !== 'dev') {
    http_response_code(404);
    exit('Not Found');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>GEMVC Framework</title>
</head>
<body>
    <h1>GEMVC Framework - Development Server</h1>
    <p>Server running on: <strong><?php echo htmlspecialchars($baseUrl); ?></strong></p>
    <p><a href="<?php echo htmlspecialchars($apiBaseUrl); ?>/Index/document">API Documentation</a></p>
</body>
</html>

