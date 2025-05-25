# Email Components

## Overview

GEMVC provides a robust email system with SMTP support, template rendering, and queue management.

## Core Components

### 1. GemSMTP (`src/email/GemSMTP.php`)
- SMTP connection management
- Email sending
- Template rendering
- Queue management

## Configuration

### SMTP Settings
```env
# SMTP Configuration
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=user@example.com
SMTP_PASSWORD=your_password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME=Your App
```

### Queue Settings
```env
# Queue Configuration
EMAIL_QUEUE_ENABLED=true
EMAIL_QUEUE_DRIVER=redis
EMAIL_QUEUE_CONNECTION=default
EMAIL_QUEUE_RETRY_AFTER=60
EMAIL_QUEUE_MAX_TRIES=3
```

## Basic Usage

### Send Simple Email
```php
use Gemvc\Email\GemSMTP;

$smtp = new GemSMTP();

$smtp->to('user@example.com')
    ->subject('Welcome to Our App')
    ->body('Thank you for joining our platform!')
    ->send();
```

### Send HTML Email
```php
$smtp->to('user@example.com')
    ->subject('Welcome to Our App')
    ->html('<h1>Welcome!</h1><p>Thank you for joining our platform!</p>')
    ->send();
```

### Send Email with Template
```php
$smtp->to('user@example.com')
    ->subject('Welcome to Our App')
    ->template('emails/welcome', [
        'name' => 'John Doe',
        'activationLink' => 'https://example.com/activate'
    ])
    ->send();
```

## Advanced Features

### Multiple Recipients
```php
$smtp->to(['user1@example.com', 'user2@example.com'])
    ->cc('manager@example.com')
    ->bcc('admin@example.com')
    ->subject('Team Update')
    ->body('Important team announcement')
    ->send();
```

### Attachments
```php
$smtp->to('user@example.com')
    ->subject('Document')
    ->body('Please find attached document')
    ->attach('path/to/document.pdf')
    ->attach('path/to/image.jpg')
    ->send();
```

### Inline Images
```php
$smtp->to('user@example.com')
    ->subject('Newsletter')
    ->html('<img src="cid:logo">')
    ->embed('path/to/logo.png', 'logo')
    ->send();
```

## Queue Management

### Queue Email
```php
$smtp->to('user@example.com')
    ->subject('Welcome')
    ->body('Welcome message')
    ->queue();
```

### Queue with Delay
```php
$smtp->to('user@example.com')
    ->subject('Reminder')
    ->body('Reminder message')
    ->delay(60) // Delay for 60 seconds
    ->queue();
```

### Queue with Priority
```php
$smtp->to('user@example.com')
    ->subject('Urgent')
    ->body('Urgent message')
    ->priority('high')
    ->queue();
```

## Template System

### Create Template
```php
// templates/emails/welcome.php
<!DOCTYPE html>
<html>
<head>
    <title><?= $subject ?></title>
</head>
<body>
    <h1>Welcome, <?= $name ?>!</h1>
    <p>Thank you for joining our platform.</p>
    <p>Click here to activate your account: <a href="<?= $activationLink ?>">Activate</a></p>
</body>
</html>
```

### Use Template
```php
$smtp->to('user@example.com')
    ->subject('Welcome to Our App')
    ->template('emails/welcome', [
        'name' => 'John Doe',
        'activationLink' => 'https://example.com/activate'
    ])
    ->send();
```

## Error Handling

### Try-Catch
```php
try {
    $smtp->to('user@example.com')
        ->subject('Test')
        ->body('Test message')
        ->send();
} catch (\Gemvc\Email\Exceptions\SmtpException $e) {
    // Handle SMTP error
    Log::error('SMTP Error: ' . $e->getMessage());
} catch (\Gemvc\Email\Exceptions\QueueException $e) {
    // Handle queue error
    Log::error('Queue Error: ' . $e->getMessage());
}
```

### Retry Logic
```php
$smtp->to('user@example.com')
    ->subject('Test')
    ->body('Test message')
    ->retry(3) // Retry 3 times
    ->send();
```

## Best Practices

### 1. Configuration
- Use environment variables
- Secure SMTP credentials
- Configure proper timeouts
- Set up queue properly

### 2. Templates
- Use consistent layout
- Keep templates simple
- Test templates
- Handle errors gracefully

### 3. Queue Management
- Use queue for bulk emails
- Set appropriate priorities
- Handle failures properly
- Monitor queue health

### 4. Error Handling
- Log all errors
- Implement retry logic
- Handle timeouts
- Monitor delivery status

### 5. Security
- Validate email addresses
- Sanitize template data
- Use secure connections
- Handle sensitive data

## Next Steps

- [Request Lifecycle](request-lifecycle.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 