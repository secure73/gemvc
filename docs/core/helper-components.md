# Helper Components

## Overview

GEMVC provides a set of helper components for common utility functions, type handling, and web operations.

## Core Components

### 1. WebHelper (`src/helper/WebHelper.php`)
- URL manipulation
- Request handling
- Response formatting
- Web utilities

### 2. TypeHelper (`src/helper/TypeHelper.php`)
- Type conversion
- Type validation
- Type checking
- Type formatting

### 3. ImageHelper (`src/helper/ImageHelper.php`)
- Image processing
- Image validation
- Image optimization
- Image formatting

## Web Helper

### URL Manipulation
```php
// Get current URL
$currentUrl = WebHelper::getCurrentUrl();

// Build URL
$url = WebHelper::buildUrl('user/profile', [
    'id' => 1,
    'tab' => 'settings'
]);

// Parse URL
$parts = WebHelper::parseUrl($url);

// Redirect
WebHelper::redirect('user/profile');
```

### Request Handling
```php
// Get request method
$method = WebHelper::getRequestMethod();

// Is AJAX request
$isAjax = WebHelper::isAjaxRequest();

// Get client IP
$ip = WebHelper::getClientIp();

// Get user agent
$userAgent = WebHelper::getUserAgent();
```

### Response Formatting
```php
// Format JSON response
$json = WebHelper::formatJsonResponse([
    'data' => $result,
    'meta' => $meta
]);

// Format HTML response
$html = WebHelper::formatHtmlResponse($content, $status);

// Set response headers
WebHelper::setResponseHeaders([
    'Content-Type' => 'application/json',
    'Cache-Control' => 'no-cache'
]);
```

## Type Helper

### Type Conversion
```php
// Convert to integer
$int = TypeHelper::toInt('123');

// Convert to float
$float = TypeHelper::toFloat('123.45');

// Convert to boolean
$bool = TypeHelper::toBool('true');

// Convert to array
$array = TypeHelper::toArray($object);
```

### Type Validation
```php
// Validate integer
$isValid = TypeHelper::isInt('123');

// Validate float
$isValid = TypeHelper::isFloat('123.45');

// Validate boolean
$isValid = TypeHelper::isBool('true');

// Validate array
$isValid = TypeHelper::isArray($value);
```

### Type Checking
```php
// Check if numeric
$isNumeric = TypeHelper::isNumeric('123.45');

// Check if string
$isString = TypeHelper::isString($value);

// Check if object
$isObject = TypeHelper::isObject($value);

// Check if null
$isNull = TypeHelper::isNull($value);
```

### Type Formatting
```php
// Format number
$formatted = TypeHelper::formatNumber(1234.56, 2);

// Format date
$formatted = TypeHelper::formatDate('2024-03-20', 'Y-m-d');

// Format time
$formatted = TypeHelper::formatTime('14:30:00', 'H:i');

// Format datetime
$formatted = TypeHelper::formatDateTime('2024-03-20 14:30:00');
```

## Image Helper

### Image Processing
```php
// Resize image
ImageHelper::resize('input.jpg', 'output.jpg', 800, 600);

// Crop image
ImageHelper::crop('input.jpg', 'output.jpg', 100, 100, 400, 300);

// Rotate image
ImageHelper::rotate('input.jpg', 'output.jpg', 90);

// Add watermark
ImageHelper::addWatermark('input.jpg', 'watermark.png', 'output.jpg');
```

### Image Validation
```php
// Validate image file
$isValid = ImageHelper::validateImage('image.jpg');

// Check image size
$isValid = ImageHelper::checkImageSize('image.jpg', 1024 * 1024);

// Check image dimensions
$isValid = ImageHelper::checkImageDimensions('image.jpg', 800, 600);

// Check image type
$isValid = ImageHelper::checkImageType('image.jpg', ['jpg', 'png']);
```

### Image Optimization
```php
// Optimize image
ImageHelper::optimize('input.jpg', 'output.jpg');

// Compress image
ImageHelper::compress('input.jpg', 'output.jpg', 80);

// Convert format
ImageHelper::convertFormat('input.jpg', 'output.png');

// Strip metadata
ImageHelper::stripMetadata('input.jpg', 'output.jpg');
```

### Image Formatting
```php
// Get image info
$info = ImageHelper::getImageInfo('image.jpg');

// Get image dimensions
$dimensions = ImageHelper::getImageDimensions('image.jpg');

// Get image type
$type = ImageHelper::getImageType('image.jpg');

// Get image size
$size = ImageHelper::getImageSize('image.jpg');
```

## Best Practices

### 1. Web Helper
- Use URL manipulation for routing
- Handle requests securely
- Format responses consistently
- Set appropriate headers

### 2. Type Helper
- Validate input types
- Convert types safely
- Check types before operations
- Format output consistently

### 3. Image Helper
- Validate images before processing
- Optimize images for web
- Handle errors gracefully
- Clean up temporary files

### 4. Error Handling
- Use appropriate error messages
- Handle edge cases
- Log errors properly
- Return meaningful results

### 5. Performance
- Cache results when possible
- Optimize image processing
- Minimize type conversions
- Use efficient algorithms

## Next Steps

- [Request Lifecycle](request-lifecycle.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 