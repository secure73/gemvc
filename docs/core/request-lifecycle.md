# Request Lifecycle

## Overview

This document explains how a request flows through the GEMVC framework, from initial HTTP request to final response.

## 1. Entry Point

### Apache Server
```apache
# .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### OpenSwoole Server
```php
// server.php
$server->on('request', function ($request, $response) {
    $bootstrap = new \Gemvc\Core\SwooleBootstrap($request);
    $apiResponse = $bootstrap->processRequest();
    // ... response handling
});
```

## 2. Bootstrap Process

```php
// index.php
$bootstrap = new \Gemvc\Core\Bootstrap();
$bootstrap->run();
```

The bootstrap process:
1. Initializes environment
2. Loads configuration
3. Sets up error handling
4. Routes the request

## 3. Request Routing

### URL Structure
```
/{service}/{method}
```

Example:
```
/user/getUsers
```

### Routing Process
1. Parse URL segments
2. Validate service exists
3. Check method exists
4. Validate request method (GET/POST)
5. Route to service method

## 4. Service Layer

### Request Validation
```php
public function getUsers(): JsonResponse
{
    // Validate request
    $this->validatePosts([
        'page' => 'int',
        'limit' => 'int'
    ]);
    
    // Process request
    return (new UserController($this->request))->list();
}
```

### Authentication
```php
// Check authentication
if(!$this->request->auth()) {
    return $this->request->returnResponse();
}

// Check roles
if(!$this->request->auth(['admin'])) {
    return $this->request->returnResponse();
}
```

## 5. Controller Layer

### Request Processing
```php
public function list(): JsonResponse
{
    // Get parameters
    $page = $this->request->getPost('page', 1);
    $limit = $this->request->getPost('limit', 10);
    
    // Process request
    $model = new UserModel();
    return $model->list($page, $limit);
}
```

### Error Handling
```php
try {
    // Process request
} catch (\Exception $e) {
    return (new JsonResponse())->error($e->getMessage());
}
```

## 6. Model Layer

### Data Processing
```php
public function list(int $page, int $limit): JsonResponse
{
    // Get data
    $table = new UserTable();
    $users = $table->select()
        ->where('is_active', true)
        ->limit($limit)
        ->offset(($page - 1) * $limit)
        ->run();
        
    return (new JsonResponse())->success($users);
}
```

### Business Logic
```php
public function create(array $data): JsonResponse
{
    // Validate business rules
    if (!$this->validateUserData($data)) {
        return (new JsonResponse())->error('Invalid user data');
    }
    
    // Process data
    $table = new UserTable();
    $userId = $table->insert($data)->run();
    
    return (new JsonResponse())->success(['id' => $userId]);
}
```

## 7. Table Layer

### Database Operations
```php
public function select(): PdoQuery
{
    return $this->query->select($this->table);
}

public function insert(array $data): PdoQuery
{
    return $this->query->insert($this->table, $data);
}

public function update(array $data): PdoQuery
{
    return $this->query->update($this->table, $data);
}

public function delete(): PdoQuery
{
    return $this->query->delete($this->table);
}
```

### Query Building
```php
$query = $table->select()
    ->where('is_active', true)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->offset(0);
```

## 8. Response Generation

### JSON Response
```php
return (new JsonResponse())->success([
    'data' => $result,
    'meta' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total
    ]
]);
```

### Error Response
```php
return (new JsonResponse())->error('Error message', 400);
```

## 9. Response Flow

1. Table returns data
2. Model processes data
3. Controller formats response
4. Service finalizes response
5. Bootstrap sends response
6. Server delivers to client

## 10. Error Handling

### Global Error Handler
```php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});
```

### Exception Handler
```php
set_exception_handler(function($exception) {
    $response = new JsonResponse();
    $response->error($exception->getMessage());
    $response->send();
});
```

## Next Steps

- [Architecture Overview](architecture.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 