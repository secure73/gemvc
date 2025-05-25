# API Documentation Generator

## Overview

GEMVC provides an automatic API documentation generator that creates interactive documentation from your API service classes. The documentation includes endpoint details, parameters, response examples, and supports Postman collection export.

## Core Features

### 1. Automatic Documentation
- Generates documentation from API service classes
- Extracts HTTP methods, URLs, and descriptions
- Parses validation rules for parameters
- Includes example responses
- Supports Postman collection export

### 2. Interactive UI
- Tree-based navigation
- Method filtering
- Parameter details
- Response examples
- Mobile-responsive design

## Basic Usage

### Accessing Documentation

There are two ways to access the API documentation:

1. HTML View (Interactive UI):
```php
use Gemvc\Core\Documentation;

// In your route handler
$doc = new Documentation();
$doc->html();
```

2. JSON Response (For programmatic use):
```php
use Gemvc\Core\Documentation;

// In your route handler
$doc = new Documentation();
return $doc->show();
```

### Documenting API Services

The documentation generator automatically reads your API service classes. Here's how to document your endpoints:

```php
<?php

namespace App\Api;

use Gemvc\Core\ApiService;

class User extends ApiService
{
    /**
     * Get user profile
     * 
     * @return JsonResponse
     * @http GET
     * @description Get the authenticated user's profile information
     * @example /api/user/profile
     */
    public function profile(): JsonResponse
    {
        // Your code here
    }

    /**
     * Create new user
     * 
     * @return JsonResponse
     * @http POST
     * @description Create a new user account
     * @example /api/user/create
     */
    public function create(): JsonResponse
    {
        $this->validatePosts([
            'name' => 'string',
            'email' => 'string',
            'password' => 'string'
        ]);
        // Your code here
    }
}
```

## Documentation Tags

### 1. Method Documentation
```php
/**
 * @http GET|POST|PUT|DELETE|PATCH
 * @description Your endpoint description
 * @example /api/endpoint/path
 */
```

### 2. Parameter Documentation
```php
/**
 * @urlparams id=int,type=string
 */
public function getItem(): JsonResponse
{
    $this->validatePosts([
        'name' => 'string',
        'email' => 'string'
    ]);
}
```

### 3. Query Parameters
```php
public function list(): JsonResponse
{
    // Define searchable fields
    $this->request->findable([
        'name' => 'string',
        'email' => 'string'
    ]);

    // Define sortable fields
    $this->request->sortable([
        'id',
        'name',
        'created_at'
    ]);
}
```

### 4. Example Responses
```php
/**
 * @hidden
 */
public static function mockResponse(string $method): array
{
    return match($method) {
        'create' => [
            'response_code' => 201,
            'message' => 'created',
            'data' => [
                'id' => 1,
                'name' => 'John Doe'
            ]
        ],
        'read' => [
            'response_code' => 200,
            'message' => 'OK',
            'data' => [
                'id' => 1,
                'name' => 'John Doe'
            ]
        ],
        default => [
            'success' => false,
            'message' => 'Unknown method'
        ]
    };
}
```

## Best Practices

### 1. Documentation Structure
- Use clear, concise descriptions
- Include example URLs
- Document all parameters
- Provide example responses

### 2. Parameter Documentation
- Document required vs optional parameters
- Specify parameter types
- Include validation rules
- Document query parameters

### 3. Response Documentation
- Include success and error examples
- Document response codes
- Show response structure
- Include pagination details

### 4. Code Organization
- Keep documentation up to date
- Use consistent formatting
- Group related endpoints
- Hide internal methods

## Example Service Template

Here's a complete example of a documented API service:

```php
<?php

namespace App\Api;

use Gemvc\Core\ApiService;
use Gemvc\Http\JsonResponse;

class User extends ApiService
{
    /**
     * Create new user
     * 
     * @return JsonResponse
     * @http POST
     * @description Create a new user account with the provided details
     * @example /api/user/create
     */
    public function create(): JsonResponse
    {
        $this->validatePosts([
            'name' => 'string',
            'email' => 'string',
            'password' => 'string'
        ]);
        return $this->controller->create();
    }

    /**
     * Get user by ID
     * 
     * @return JsonResponse
     * @http GET
     * @description Get user details by their ID
     * @example /api/user/read/?id=1
     * @urlparams id=int
     */
    public function read(): JsonResponse
    {
        $this->validatePosts([]);
        $id = $this->request->intValueGet("id");
        return $this->controller->read($id);
    }

    /**
     * List users with filtering
     * 
     * @return JsonResponse
     * @http GET
     * @description Get list of users with filtering and sorting options
     * @example /api/user/list/?sort_by=name&find_like=name=john
     */
    public function list(): JsonResponse
    {
        $this->request->findable([
            'name' => 'string',
            'email' => 'string'
        ]);

        $this->request->sortable([
            'id',
            'name',
            'email',
            'created_at'
        ]);
        
        return $this->controller->list();
    }

    /**
     * @hidden
     */
    public static function mockResponse(string $method): array
    {
        return match($method) {
            'create' => [
                'response_code' => 201,
                'message' => 'created',
                'data' => [
                    'id' => 1,
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ]
            ],
            'read' => [
                'response_code' => 200,
                'message' => 'OK',
                'data' => [
                    'id' => 1,
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ]
            ],
            'list' => [
                'response_code' => 200,
                'message' => 'OK',
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'John Doe',
                        'email' => 'john@example.com'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Jane Doe',
                        'email' => 'jane@example.com'
                    ]
                ]
            ],
            default => [
                'success' => false,
                'message' => 'Unknown method'
            ]
        };
    }
}
```

## Next Steps

- [API Features](api.md)
- [Authentication Guide](../guides/authentication.md)
- [Security Guide](../guides/security.md) 