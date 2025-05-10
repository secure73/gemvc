# geminstaller
installer package for gemvc framework

## Overview
GEMVC Framework is built on top of GEMVC Library (v3.27.8), providing a structured approach to building microservice-based RESTful APIs. The framework adds additional features and conventions on top of the library's core functionality.

## AI Assistant Learning Process (RESTRICTED - HIGH IMPORTANCE)
For AI assistants, the following learning process is mandatory and must be followed in strict order:

1. **Study Working Examples** (First Priority)
   - Location: `/app` folder
   - Purpose: Understand real-world implementation patterns
   - Files to study:
     - `/app/api/*.php` - API service implementations
     - `/app/controller/*.php` - Controller implementations
     - `/app/model/*.php` - Model implementations
     - `/app/table/*.php` - Table implementations
   - Focus: Learn from existing working code before attempting any implementation

2. **Read Framework Documentation**
   - Location: `README.md` (this file)
   - Purpose: Understand framework structure and basic concepts
   - Focus: Framework overview, installation, and basic usage

3. **Study AI Assistant Rules**
   - Location: `GEMVCAIAssistantRules.json`
   - Purpose: Understand framework-specific guidelines
   - Focus: Rules, conventions, and best practices

4. **Framework and Library Documentation**
   - Location: 
     - `/vendor/gemvc/framework/Documentation.md`
     - `/vendor/gemvc/library/Documentation.md`
   - Purpose: Deep understanding of framework and library capabilities
   - Focus: Detailed technical documentation

### Important Restrictions for AI Assistants
- Must follow the learning process in strict order
- No implementation attempts before understanding working examples
- All implementations must be verified against existing examples
- Must follow established patterns strictly
- Must respect framework conventions

## Installation
```bash
# Install with project name folder
composer create-project gemvc/installer [your_project_name]

# Install in current folder
composer create-project gemvc/installer .
```

## Framework Structure

### Core Components
All core framework components are located in the `Gemvc\Core` namespace:

```php
use Gemvc\Core\Auth;           // Authentication and authorization
use Gemvc\Core\ApiService;     // Base service class
use Gemvc\Core\Controller;     // Base controller class
use Gemvc\Core\CRUDTable;      // Base table class for CRUD operations
use Gemvc\Core\Table;          // Base table class
use Gemvc\Core\Bootstrap;      // Framework bootstrap
```

### Namespace Structure
```
Gemvc\
├── Core\           # Core framework components
│   ├── Auth.php
│   ├── ApiService.php
│   ├── Controller.php
│   ├── CRUDTable.php
│   └── Bootstrap.php
└── Http\           # HTTP handling components
    ├── Request.php
    └── JsonResponse.php
```

### Application Structure
The framework follows a layered architecture pattern:

```
/app
├── api/         # API service layer - handles endpoints and request validation
├── controller/  # Business logic layer - implements application logic
├── model/       # Data models - represents data structures
├── table/       # Database table definitions - handles database operations
└── .env         # Environment configuration - stores application settings
```

#### Layer Responsibilities
- **API Layer** (`/app/api`): 
  - Handles HTTP requests and responses
  - Validates input data
  - Routes requests to appropriate controllers
  - Implements API documentation

- **Controller Layer** (`/app/controller`):
  - Contains business logic
  - Processes validated requests
  - Interacts with models
  - Returns processed results

- **Model Layer** (`/app/model`):
  - Defines data structures
  - Extends (inherit) from its relevant table layer file example: TodoModel extends TodoTable
  - Allways must call parent::__construct(); in its constructor
  - must not  use CRUDTable or  Table , because its parent  already extended from Table or CRUDTable!
  - Implements data validation rules
  - Handles data relationships

- **Table Layer** (`/app/table`):
  - Manages database operations
  - Allways Extend (inherit) from Table OR CRUDTable example : TodoTable extends CRUDTable
  - Allways must call parent::__construct(); in its constructor
  - Implements CRUD operations
  - Handles database relationships

#### Environment Configuration
The framework uses Symfony's Dotenv component for environment management:
```php
// index.php
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/app/.env');
```

### Important Notes
1. Always use `Gemvc\Core\Auth` for authentication (not `Gemvc\Auth\Auth`)
2. Core components are in the `Core` namespace
3. HTTP components are in the `Http` namespace
4. Database components are in the `Database` namespace
5. When manually setting POST parameters, use array syntax: `$request->post['key'] = $value` (not `setPost()` method)
6. Auth token user ID is accessed via `$auth->token->user_id` (not `$auth->token->id`)
7. For string validation with length constraints, use `validateStringPosts()` with format: `['field'=>'min|max']`
8. For email validation, use `validatePosts()` with format: `['email'=>'email']`
9. The Auth class automatically handles invalid tokens by returning a 403 response and stopping execution

### Input Validation Best Practices
The framework provides different validation methods for different types of input:

#### Authentication
```php
// Auth class automatically handles invalid tokens
$auth = new Auth($this->request);
// If token is invalid, execution stops with 403 response
// If token is valid, you can safely access user data
$this->request->post['id'] = $auth->token->user_id;
```

#### Basic Validation
```php
// For email and basic type validation
$this->validatePosts([
    'email' => 'email',
    'password' => 'string'
]);
```

#### String Length Validation
```php
// For string lenght validation constraints
$this->validateStringPosts([
    'password' => '6|15'  // min length 6, max length 15
]);
```

#### Validation Flow
1. Always validate authentication first - Auth class will handle invalid tokens automatically
2. Validate input before processing
3. Use appropriate validation method based on input type
4. Set additional parameters after validation
5. Pass validated request to controller

Example of a secure endpoint:
```php
public function updatePassword(): JsonResponse
{
    // 1. Check authentication - automatically stops with 403 if token is invalid
    $auth = new Auth($this->request);
    
    // 2. Validate input with length constraints
    $this->validateStringPosts(['password'=>'6|15']);
    
    // 3. Set additional parameters - safe to do after Auth check
    $this->request->post['id'] = $auth->token->user_id;
    
    // 4. Process request
    return (new UserController($this->request))->updatePassword();
}
```

## Documentation
The complete API documentation for your application is available at:
```
http://your-domain/index/document
```

This documentation is automatically generated from your code's PHPDoc comments and mock responses.

## Database Setup
Create the users table with the following structure:
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    role VARCHAR(50) NOT NULL DEFAULT 'user'
);
```

## API Documentation Generation

### Mock Response System
The GEMVC Framework includes an automatic API documentation generation system using the `mockResponse` static method. This feature helps maintain accurate and up-to-date API documentation.

#### How to Implement
1. Add a static `mockResponse` method to your API service class:
```php
/**
 * Generates mock responses for API documentation
 * 
 * @param string $method The API method name
 * @return array<mixed> Example response data for the specified method
 * @hidden
 */
public static function mockResponse(string $method): array
{
    return match($method) {
        'yourMethod' => [
            'response_code' => 200,
            'message' => 'OK',
            'count' => 1,
            'service_message' => 'Operation successful',
            'data' => [
                // Your example response data
            ]
        ],
        default => [
            'success' => false,
            'message' => 'Unknown method'
        ]
    };
}
```

#### Response Structure
Your mock responses should follow this structure:
```php
[
    'response_code' => int,      // HTTP status code
    'message' => string,         // Status message
    'count' => int,             // Number of items returned
    'service_message' => string, // Detailed service message
    'data' => mixed            // Response data
]
```

#### Best Practices
1. Always include the `@hidden` annotation to mark the method as internal
2. Provide realistic example data that matches your actual response structure
3. Include all possible response variations (success, error cases)
4. Keep the example data up to date with your actual API responses
5. Use proper type hints and return type declarations

#### Example
```php
public static function mockResponse(string $method): array
{
    return match($method) {
        'create' => [
            'response_code' => 201,
            'message' => 'created',
            'count' => 1,
            'service_message' => 'Resource created successfully',
            'data' => [
                'id' => 1,
                'name' => 'Example Resource'
            ]
        ],
        'error' => [
            'response_code' => 400,
            'message' => 'Bad Request',
            'count' => 0,
            'service_message' => 'Invalid input data',
            'data' => null
        ],
        default => [
            'success' => false,
            'message' => 'Unknown method'
        ]
    };
}
```

## AI Assistant Support
The GEMVC Framework includes comprehensive AI Assistant rules to ensure consistent and secure development assistance. These rules are defined in `GEMVCAIAssistantRules.json` and cover:

### Key AI Assistant Guidelines
1. **Core Principles**
   - Security-first approach
   - Strict type safety (PHPStan level 9)
   - Respect for layered architecture
   - Framework convention adherence

2. **Architecture Understanding**
   - Layer-specific responsibilities
   - Proper inheritance requirements
   - Access control between layers
   - Component relationships

3. **Security Enforcement**
   - Authentication patterns
   - Input validation methods
   - Parameter handling
   - Token validation

4. **Response Standards**
   - Consistent response formats
   - HTTP status code mapping
   - Error handling patterns

### AI Assistant Resources
The framework provides several resources to support AI-assisted development:

1. **Framework AI Assist**
   - Location: `vendor/gemvc/framework/GEMVCFrameworkAIAssist.jsonc`
   - Purpose: Framework-specific AI assistance rules
   - Features: Architecture patterns, security rules, best practices

2. **Library AI Assist**
   - Location: `vendor/gemvc/library/AIAssist.jsonc`
   - Purpose: Core library AI assistance rules
   - Features: Component usage, error handling, security patterns

3. **API References**
   - Framework: `vendor/gemvc/framework/GEMVCFrameworkAPIReference.json`
   - Library: `vendor/gemvc/library/GEMVCLibraryAPIReference.json`
   - Purpose: Detailed API documentation for AI assistance

### AI Assistant Best Practices
When working with AI assistants, follow these guidelines:

1. **Code Generation**
   - Always verify generated code against framework rules
   - Ensure proper layer separation
   - Validate security implementations
   - Check type safety compliance

2. **Documentation**
   - Use PHPDoc comments for all public methods
   - Include mock responses for API endpoints
   - Provide clear examples in comments
   - Follow framework documentation standards

3. **Security**
   - Verify authentication implementations
   - Validate input handling
   - Check parameter setting methods
   - Ensure proper error responses

## IMPORTANT Resources
- [GEMVC Framework Documentation](vendor/gemvc/framework/Documentation.md)
- [GEMVC Framework API Reference](vendor/gemvc/framework/GEMVCFrameworkAPIReference.json)
- [GEMVC Framework AI Assist](vendor/gemvc/framework/GEMVCFrameworkAIAssist.jsonc)
- [GEMVC Library Documentation](vendor/gemvc/library/Documentation.md)
- [GEMVC Library API Reference](vendor/gemvc/library/GEMVCLibraryAPIReference.json)
- [GEMVC Library AI Assist](vendor/gemvc/library/AIAssist.jsonc)
- [GEMVC AI Assistant Rules](GEMVCAIAssistantRules.json)
