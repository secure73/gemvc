# GEMVC Framework Index

## 1. Core Components (src/core/)

### Application Bootstrap
- **Bootstrap.php** (7.9KB, 223 lines)
  - Routes HTTP requests
  - Handles API and web requests
  - Manages error responses
  - Integrates with service layer

- **SwooleBootstrap.php** (2.5KB, 80 lines)
  - Adapts Bootstrap for OpenSwoole
  - Handles async request processing
  - Manages Swoole-specific responses

### Service Layer
- **ApiService.php** (2.5KB, 79 lines)
  - Defines API endpoint structure
  - Implements request validation
  - Handles response formatting
  - Provides authentication methods

- **SwooleApiService.php** (3.1KB, 107 lines)
  - Extends ApiService for Swoole
  - Adapts for async processing
  - Maintains Swoole compatibility

### Controller Layer
- **Controller.php** (7.0KB, 220 lines)
  - Handles request processing
  - Manages model interactions
  - Implements security features
  - Provides response formatting

### Documentation
- **Documentation.php** (32KB, 849 lines)
  - Generates API documentation
  - Supports annotations
  - Creates OpenAPI specs

- **ApiDocGenerator.php** (13KB, 385 lines)
  - Analyzes API services
  - Generates documentation
  - Supports multiple formats

### System Components
- **Runner.php** (1.9KB, 78 lines)
  - Processes CLI commands
  - Manages system operations
  - Handles maintenance tasks

- **RedisManager.php** (9.9KB, 389 lines)
  - Handles Redis connections
  - Implements connection pooling
  - Manages Redis operations
  - Provides JsonResponse caching
  - Supports serialized response storage
  - Handles TTL for cached responses

- **RedisConnectionException.php** (288B, 12 lines)
  - Handles Redis connection errors
  - Provides specific error messages
  - Implements error handling

## 2. Database Components (src/database/)

### Core Database Classes
- **Table.php** (31KB, 1043 lines)
  - Provides ORM-like interface
  - Implements CRUD operations
  - Supports type mapping
  - Handles relationships

- **PdoQuery.php** (9.1KB, 247 lines)
  - Extends QueryExecuter
  - Implements query building
  - Handles parameter binding

- **QueryExecuter.php** (12KB, 409 lines)
  - Manages SQL execution
  - Handles error management
  - Provides result formatting

- **PdoConnection.php** (9.9KB, 335 lines)
  - Implements connection pooling
  - Handles connection health
  - Manages resource tracking

### Database Management
- **DBPoolManager.php** (7.3KB, 256 lines)
  - Manages database connections
  - Implements connection reuse
  - Handles pool configuration

- **DBConnection.php** (1.6KB, 67 lines)
  - Manages database connections
  - Implements connection options
  - Handles connection state

### Schema Management
- **TableGenerator.php** (35KB, 900 lines)
  - Creates database tables
  - Manages schema updates
  - Handles migrations

### Query Building
- **QueryBuilder.php** (1.9KB, 79 lines)
  - Builds SQL queries
  - Manages query state
  - Handles error tracking

- **QueryBuilderInterface.php** (156B, 9 lines)
  - Defines query methods
  - Standardizes query operations

- **SqlEnumCondition.php** (418B, 28 lines)
  - Defines SQL operators
  - Standardizes conditions

### Query Components (src/database/query/)
- **Select.php** (5.9KB, 240 lines)
  - Builds SELECT queries
  - Implements query conditions
  - Handles result formatting

- **Insert.php** (2.9KB, 128 lines)
  - Builds INSERT queries
  - Handles value binding
  - Manages auto-increment

- **Update.php** (2.7KB, 116 lines)
  - Builds UPDATE queries
  - Implements set operations
  - Handles conditions

- **Delete.php** (2.2KB, 99 lines)
  - Builds DELETE queries
  - Implements conditions
  - Handles cascading

- **WhereTrait.php** (2.7KB, 92 lines)
  - Provides WHERE clause functionality
  - Implements condition building
  - Handles operators

- **LimitTrait.php** (1.3KB, 60 lines)
  - Provides LIMIT functionality
  - Implements pagination
  - Handles offsets

## 3. HTTP Components (src/http/)

### Request Handling
- **Request.php** (30KB, 899 lines)
  - Handles request validation
  - Manages authentication
  - Processes input data

- **ApacheRequest.php** (8.4KB, 246 lines)
  - Adapts for Apache
  - Handles traditional PHP

- **SwooleRequest.php** (11KB, 307 lines)
  - Adapts for Swoole
  - Handles async requests

### Response Handling
- **Response.php** (4.3KB, 93 lines)
  - Manages HTTP responses
  - Handles status codes
  - Formats output

- **JsonResponse.php** (6.3KB, 191 lines)
  - Formats JSON output
  - Handles API responses

- **HtmlResponse.php** (997B, 36 lines)
  - Formats HTML output
  - Handles web responses

- **ResponseInterface.php** (118B, 8 lines)
  - Defines response methods
  - Standardizes responses

### Authentication
- **JWTToken.php** (8.3KB, 255 lines)
  - Manages JWT tokens
  - Handles authentication
  - Implements security

### WebSocket
- **SwooleWebSocketHandler.php** (27KB, 814 lines)
  - Manages WebSocket connections
  - Handles real-time communication
  - Implements channels

### Utilities
- **ApiCall.php** (5.5KB, 201 lines)
  - Makes HTTP requests
  - Handles responses
  - Manages errors

- **NoCors.php** (3.5KB, 88 lines)
  - Manages CORS headers
  - Handles cross-origin requests

## 4. CLI Components (src/CLI/)

### Command System
- **Command.php** (2.2KB, 88 lines)
  - Defines command structure
  - Handles command execution
  - Manages output

- **InstallationTest.php** (809B, 27 lines)
  - Tests installation
  - Verifies setup
  - Checks requirements

### Code Generation
- **CreateService.php** (18KB, 589 lines)
  - Creates API services
  - Generates endpoints
  - Implements CRUD

- **CreateController.php** (11KB, 379 lines)
  - Creates controllers
  - Implements actions
  - Handles models

- **CreateModel.php** (8.3KB, 282 lines)
  - Creates models
  - Implements traits
  - Handles data

- **CreateTable.php** (4.9KB, 165 lines)
  - Creates tables
  - Implements schema
  - Handles migrations

### Project Management
- **InitProject.php** (18KB, 461 lines)
  - Creates project structure
  - Sets up configuration
  - Initializes environment

- **Setup.php** (7.1KB, 196 lines)
  - Configures environment
  - Sets up server
  - Manages settings

- **Migrate.php** (2.5KB, 75 lines)
  - Handles migrations
  - Updates schema
  - Manages versions

### Base Generators
- **BaseGenerator.php** (3.7KB, 139 lines)
  - Base class for generators
  - Implements common functionality
  - Handles file operations

- **BaseCrudGenerator.php** (4.0KB, 146 lines)
  - Base class for CRUD generators
  - Implements CRUD operations
  - Handles model generation

- **CreateCrud.php** (1.7KB, 58 lines)
  - Generates complete CRUD
  - Creates all necessary files
  - Implements full stack

## 5. Helper Components (src/helper/)

### Project Management
- **ProjectHelper.php** (1.2KB, 48 lines)
  - Manages project paths
  - Finds project root directory
  - Handles environment loading
  - Provides app directory access
  - Uses composer.lock for root detection

### Type Handling
- **TypeHelper.php** (2.6KB, 106 lines)
  - Handles type conversion
  - Manages validation
  - Provides utilities

- **TypeChecker.php** (7.3KB, 192 lines)
  - Validates types
  - Checks values
  - Handles errors

### File Handling
- **FileHelper.php** (7.2KB, 251 lines)
  - Manages files
  - Handles uploads
  - Implements security

- **ImageHelper.php** (8.0KB, 296 lines)
  - Processes images
  - Handles optimization
  - Manages formats

### Utilities
- **StringHelper.php** (5.2KB, 157 lines)
  - Manages strings
  - Handles formatting
  - Provides utilities

- **JsonHelper.php** (1.8KB, 66 lines)
  - Processes JSON
  - Handles encoding
  - Manages errors

- **CryptHelper.php** (2.9KB, 83 lines)
  - Handles encryption
  - Manages security
  - Provides hashing

- **WebHelper.php** (4.1KB, 125 lines)
  - Handles web operations
  - Manages requests
  - Provides helpers

- **ChatGptClient.php** (1.2KB, 43 lines)
  - Interfaces with AI
  - Handles requests
  - Manages responses

## 6. Email Component (src/email/)

- **GemSMTP.php** (14KB, 410 lines)
  - Manages SMTP
  - Handles sending
  - Implements security

## 7. Traits (src/traits/)

### Model Traits
- **CreateModelTrait.php** (1.4KB, 54 lines)
- **UpdateTrait.php** (545B, 22 lines)
- **DeleteTrait.php** (1.3KB, 48 lines)
- **IdTrait.php** (1.7KB, 77 lines)
- **ListTrait.php** (6.3KB, 211 lines)
- **ActivateTrait.php** (1.6KB, 55 lines)
- **DeactivateTrait.php** (323B, 19 lines)
- **RemoveTrait.php** (1.3KB, 48 lines)
- **RestoreTrait.php** (822B, 33 lines)
- **SafeDeleteModelTrait.php** (327B, 19 lines)
- **ListObjectTrait.php** (1.0KB, 42 lines)
- **ListObjectTrashTrait.php** (1.0KB, 41 lines)
- **ListTrashTrait.php** (1.1KB, 43 lines)

### Controller Traits
- **IdTrait.php** (430B, 20 lines)
- **DeleteTrait.php** (444B, 19 lines)
- **RemoveTrait.php** (446B, 20 lines)
- **RestoreTrait.php** (448B, 19 lines)
- **TrashTrait.php** (538B, 18 lines)
- **ActivateTrait.php** (454B, 20 lines)
- **DeactivateTrait.php** (462B, 20 lines)

## 8. Startup Components (src/startup/)

### Server Configuration
- **apache/**: Apache setup
  - Apache configuration
  - PHP-FPM setup
  - Server settings

- **swoole/**: Swoole setup
  - Swoole configuration
  - Server settings
  - Async setup

## 9. Architecture Flow

### Request Flow
```
Client Request → Server (Apache/Swoole) → Bootstrap → Service → Controller → Model → Table → Database
```

### Response Flow
```
Database → Table → Model → Controller → Service → Response → Client
```

### Authentication Flow
```
Request → JWT Validation → Role Check → Service/Controller → Response
```

### WebSocket Flow
```
Client → SwooleWebSocketHandler → Redis (optional) → Client
```

### CLI Flow
```
Command → Runner → Generator/Manager → File System/Database
``` 