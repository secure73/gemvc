<?php
/**
 * 404 Page Not Found Template
 * 
 * This template is shown when a requested web page does not exist.
 * In production, this provides a generic error message for security.
 */

// Security check: Only allow system pages in development mode
// Note: This is a generic error page, but we still check to prevent direct access
if (isset($_ENV['APP_ENV']) && ($_ENV['APP_ENV'] ?? '') !== 'dev') {
    // In production, this page should only be shown through Bootstrap
    // Direct access is blocked for security
    http_response_code(404);
    exit('Not Found');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { font-size: 36px; color: #333; }
        p { font-size: 18px; color: #666; }
    </style>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>The page you are looking for does not exist.</p>
</body>
</html>

