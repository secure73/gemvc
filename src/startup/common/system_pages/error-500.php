<?php
/**
 * 500 Server Error Template
 * 
 * This template is shown when a server error occurs during web request handling.
 * 
 * @var \Throwable $exception The exception that caused the error
 * @var bool $debugMode Whether debug mode is enabled (from $_ENV['DEBUG'])
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
    <title>500 - Server Error</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); min-height: 100vh; }
        h1 { font-size: 36px; color: #10b981; }
        p { font-size: 18px; color: #666; }
        .error { color: #059669; text-align: left; margin: 20px auto; max-width: 800px; background: #f0fdf4; padding: 15px; border-left: 4px solid #10b981; border-radius: 4px; }
        pre { text-align: left; background: #f8f8f8; padding: 15px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <h1>500 - Server Error</h1>
    <p>An error occurred while processing your request.</p>
    <?php if ($debugMode): ?>
        <div class="error">
            <p><?php echo htmlspecialchars($exception->getMessage()); ?></p>
            <pre><?php echo htmlspecialchars($exception->getTraceAsString()); ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>

