# Configuration Guide

## Environment Configuration

GEMVC uses environment variables for configuration. These can be set in your `.env` file or through your server's environment.

### Essential Configuration

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_db
DB_USER=root
DB_PASSWORD='yourPassword'

# Security Settings
TOKEN_SECRET='your_secret'
TOKEN_ISSUER='your_api'
```

### Optional Configuration

```env
# Database Cache
DB_CACHE_ENABLED=true
DB_CACHE_TTL_SEC=3600
DB_CACHE_MAX_QUERY_SIZE=1000

# Connection Pooling
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=5
DB_CONNECTION_MAX_AGE=3600

# OpenSwoole Configuration
SWOOLE_MODE=true
OPENSWOOLE_WORKERS=3
OPEN_SWOOLE_ACCEPT_REQUEST='0.0.0.0'
OPEN_SWOOLE_ACCPT_PORT=9501

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DATABASE=0
```

## Server Configuration

### Apache Configuration

1. Enable required modules:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
```

2. Configure virtual host:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/your/project/public
    
    <Directory /path/to/your/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### OpenSwoole Configuration

Create a `server.php` file in your project root:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

$server = new \OpenSwoole\HTTP\Server('0.0.0.0', 9501);

$server->set([
    'worker_num' => 4,
    'max_request' => 1000,
    'enable_coroutine' => true,
    'document_root' => __DIR__ . '/public',
    'enable_static_handler' => true
]);

$server->on('request', function ($request, $response) {
    $bootstrap = new \Gemvc\Core\SwooleBootstrap($request);
    $apiResponse = $bootstrap->processRequest();
    
    if ($apiResponse instanceof \Gemvc\Http\JsonResponse) {
        $response->header('Content-Type', 'application/json');
        $response->end($apiResponse->toJson());
    } else if ($apiResponse instanceof \Gemvc\Http\HtmlResponse) {
        $response->header('Content-Type', 'text/html');
        $response->end($apiResponse->getContent());
    }
});

$server->start();
```

## Security Configuration

### JWT Authentication

Configure JWT settings in your `.env`:

```env
# JWT Configuration
TOKEN_SECRET='your-secure-secret'
TOKEN_ISSUER='your-api-name'
LOGIN_TOKEN_VALIDATION_IN_SECONDS=789000
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
ACCESS_TOKEN_VALIDATION_IN_SECONDS=1200
```

### CORS Configuration

Configure CORS in your `.env`:

```env
# CORS Configuration
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization
CORS_MAX_AGE=3600
```

## Database Configuration

### Connection Pooling

Configure connection pooling in your `.env`:

```env
# Connection Pool Settings
MIN_DB_CONNECTION_POOL=2
MAX_DB_CONNECTION_POOL=5
DB_CONNECTION_MAX_AGE=3600
DB_CONNECTION_TIME_OUT=20
DB_CONNECTION_EXPIER_TIME=20
```

### Query Limits

```env
# Query Settings
QUERY_LIMIT=10
MAX_QUERY_LIMIT=100
```

## Next Steps

- [Quick Start Guide](quick-start.md)
- [Core Features](../features/README.md)
- [Security Guide](../guides/security.md) 