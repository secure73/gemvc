# GEMVC Framework Index

## 1. Database Components

### Core Database Classes (src/database/)
- **Table.php**: ORM-like database abstraction layer with fluent interface
  - Extends PdoQuery
  - Provides typed CRUD operations
  - Supports pagination, filtering, and sorting
  - Includes property type mapping system
  - Implements soft delete and restoration capabilities

- **PdoQuery.php**: Higher-level database operations
  - Extends QueryExecuter
  - Provides methods for select, insert, update, and delete operations
  - Handles parameter binding and result formatting

- **QueryExecuter.php**: Core query execution layer
  - Aggregates PdoConnection
  - Manages SQL execution and error handling
  - Provides fetch methods and affected row counts
  - Implements automatic binding based on value types

- **PdoConnection.php**: Database connection management
  - Handles connection pooling with parametric-based keys
  - Supports connection reuse, health verification, and expiration
  - Provides efficient resource tracking
  - Configurable through environment variables

- **TableGenerator.php**: Schema management
  - Creates and updates database tables from PHP objects
  - Maps PHP types to SQL datatypes
  - Supports indexes, constraints, and relationships
  - Provides schema migration capabilities

- **QueryBuilder.php**: SQL query builder
  - Provides a fluent interface for building SQL queries
  - Interfaces with the QueryExecuter for execution

- **SqlEnumCondition.php**: Enumeration for SQL condition types
  - Defines standard SQL conditions for query building

### Query Components (src/database/query/)
- **Insert.php**: Insert query building
- **Select.php**: Select query building
- **Update.php**: Update query building 
- **Delete.php**: Delete query building
- **WhereTrait.php**: Where clause functionality
- **LimitTrait.php**: Pagination limit functionality

## 2. HTTP Components (src/http/)

- **Request.php**: Base request handling
  - Provides validation, filtering, and authentication
  - Supports type-safe value extraction
  - Implements schema validation for request data
  - Handles JWT token management and role-based authorization
  - Provides built-in authentication methods (auth, userId, userRole)

- **ApacheRequest.php**: Apache/Nginx HTTP request adapter
  - Specializes Request for traditional PHP environments

- **SwooleRequest.php**: OpenSwoole HTTP request adapter
  - Specializes Request for high-performance Swoole server

- **Response.php**: HTTP response handling
  - Manages HTTP status codes and headers
  - Provides consistent error formatting

- **JsonResponse.php**: JSON response formatting
  - Standardizes API responses with success/error states

- **JWTToken.php**: JWT token management
  - Handles token generation, validation, and parsing
  - Supports role-based authentication

- **ApiCall.php**: External API request client
  - Manages outgoing HTTP requests
  - Supports request forwarding

- **NoCors.php**: CORS header management
  - Provides cross-origin request support

- **SwooleWebSocketHandler.php**: WebSocket server functionality
  - Manages WebSocket connections with heartbeat
  - Supports channels for pub/sub messaging
  - Includes Redis integration for horizontal scaling
  - Implements rate limiting and authentication

## 3. Helper Components (src/helper/)

- **TypeHelper.php**: Type utility functions
  - Provides GUID generation and timestamp formatting
  - Supports reflection-based property discovery

- **TypeChecker.php**: Value validation
  - Validates values against types
  - Supports primitive types and complex validation

- **FileHelper.php**: File manipulation
  - Handles secure file uploads
  - Supports file encryption/decryption
  - Provides path safety checks

- **ImageHelper.php**: Image processing
  - Handles image manipulation and optimization
  - Supports WebP conversion
  - Implements image resizing and cropping

- **StringHelper.php**: String utility functions
  - Provides string manipulation and validation

- **JsonHelper.php**: JSON utility functions
  - Handles JSON parsing and formatting with error handling

- **CryptHelper.php**: Encryption utilities
  - Provides secure encryption/decryption functions
  - Implements password hashing and verification

- **WebHelper.php**: Web-related utilities
  - HTTP request handling functions

- **ChatGptClient.php**: AI integration
  - Provides interface for OpenAI API requests

## 4. Email Component (src/email/)

- **GemSMTP.php**: Email sending functionality
  - Implements secure SMTP connections with retry support
  - Provides HTML email formatting
  - Handles attachments and embedded images
  - Includes content security checks

## 5. Core Components (src/core/)

- **Bootstrap.php**: Application bootstrap
  - Routes and handles HTTP requests
  - Determines whether to handle requests as API or web
  - Executes appropriate service/controller methods
  - Handles errors with proper HTTP responses

- **SwooleBootstrap.php**: Swoole application bootstrap
  - Adapted version of Bootstrap for OpenSwoole
  - Returns responses instead of using die()
  - Maintains same functionality with Swoole compatibility

- **Controller.php**: Base controller functionality
  - Provides request handling and error management
  - Implements pagination, filtering, and sorting
  - Offers security features like input sanitization
  - Includes methods for handling database models

- **ApiService.php**: API service base class
  - Defines structure for REST API endpoints
  - Implements validation methods for request data
  - Provides error handling and response formatting
  - Includes mock response support for documentation

- **SwooleApiService.php**: Swoole API service base class
  - Adapted version of ApiService for OpenSwoole
  - Returns responses instead of using die()
  - Maintains same functionality with Swoole compatibility

- **Runner.php**: Command execution system
  - Processes CLI commands
  - Manages table creation and other operations
  - Supports migrations and other maintenance tasks

- **Documentation.php**: Documentation generation
  - Creates API documentation from source code
  - Supports annotations for endpoint documentation
  - Generates Swagger/OpenAPI specifications

- **ApiDocGenerator.php**: API documentation tool
  - Analyzes API services to create documentation
  - Supports multiple output formats
  - Provides examples and request/response samples

## 6. CLI Components (src/CLI/)

- **Command.php**: Base command class
  - Provides structure for console commands
  - Includes colorized output with ANSI support
  - Handles command arguments and options
  - Implements common command functionality

- **InstallationTest.php**: Framework installation verification
  - Checks if framework is properly installed
  - Verifies autoloader configuration
  - Validates directory permissions and structure

### CLI Commands (src/CLI/commands/)

- **CreateService.php**: Service generation command
  - Creates boilerplate code for new API services
  - Generates controller, model, and table classes
  - Implements common CRUD operations
  - Creates proper directory structure

- **InitProject.php**: Project initialization command
  - Creates basic directory structure
  - Sets up configuration files
  - Configures global CLI command wrapper

- **Setup.php**: Platform configuration command
  - Sets up environment for specific platforms (Apache/Swoole)
  - Copies platform-specific files and configurations
  - Configures environment settings for the chosen platform

- **Migrate.php**: Database migration command
  - Executes database migrations
  - Tracks migration status
  - Ensures database schema is up-to-date

## 7. Traits (src/traits/)

### Model Traits (src/traits/model/)

- **ActivateTrait.php**: Model activation functionality
  - Implements activation/deactivation methods
  - Provides response formatting for activation operations

- **CreateTrait.php**: Creation operations
  - Handles database record creation
  - Formats responses for create operations

- **DeleteTrait.php**: Deletion operations
  - Manages record deletion
  - Provides standardized delete functionality

- **IdTrait.php**: ID-based operations
  - Implements record retrieval by ID
  - Handles validation for ID parameters

- **ListTrait.php**: List operations
  - Implements pagination, sorting, and filtering
  - Provides standardized list functionality

- **RemoveTrait.php**: Record removal
  - Handles permanent deletion of records
  - Formats responses for removal operations

- **RestoreTrait.php**: Restoration operations
  - Restores soft-deleted records
  - Provides standardized restore functionality

- **UpdateTrait.php**: Update operations
  - Handles record updates
  - Formats responses for update operations

- **ListObjectTrait.php**: Object listing
  - Specialized listing for object collections
  - Object-oriented approach to listing

- **ListTrashTrait.php**: Trash listing
  - Lists soft-deleted records
  - Provides trash management operations

### Controller Traits (src/traits/controller/)

- **ActivateTrait.php**: Controller activation methods
  - Exposes activation endpoints
  - Handles activation request validation

- **DeactivateTrait.php**: Deactivation functionality
  - Exposes deactivation endpoints
  - Validates deactivation requests

- **DeleteTrait.php**: Deletion endpoints
  - Provides delete operation handlers
  - Validates deletion requests

- **IdTrait.php**: ID-based operations
  - Implements ID validation and handling
  - Provides methods for ID-based operations

- **RemoveTrait.php**: Removal endpoints
  - Exposes permanent deletion operations
  - Validates removal requests

- **RestoreTrait.php**: Restoration endpoints
  - Implements soft-delete restoration
  - Validates restoration requests

- **TrashTrait.php**: Trash management
  - Provides endpoints for trash viewing and management
  - Implements trash-related operations

## 8. Architecture Summary

### Database Flow
```
Request → Controller → Table/QueryBuilder → PdoQuery → QueryExecuter → PdoConnection → MySQL/MariaDB
```

### HTTP Request Flow
```
Client Request → Apache/Swoole → ApacheRequest/SwooleRequest → Request → Bootstrap → ApiService/Controller → JsonResponse → Client
```

### WebSocket Flow
```
Client WebSocket → OpenSwoole → SwooleWebSocketHandler → [Redis for scaling] → Client WebSocket
```

### CLI Flow
```
Terminal → Command Entry Point → Command → Runner → Database/File Operations
```

### Authentication Flow
```
Client Request → Request.auth() → JWT Validation → userRole()/userId() → Role-based Access
``` 