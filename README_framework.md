# 🚀 GEMVC Framework

Built on top of the powerful GEMVC Library, the GEMVC Framework provides a complete solution for building secure, type-safe PHP applications.

## 📚 Table of Contents
1. [AI-Ready Architecture](#-ai-ready-architecture)
2. [Framework Architecture](#️-framework-architecture)
3. [Key Features](#-key-features)
4. [GEMVC Core](#-gemvc)
5. [Quick Start](#-quick-start)
6. [Core Features](#-core-features)
7. [Documentation](Documentation.md)

## 🤖 AI-Ready Architecture

### AI Integration Files
- **Framework:**
  - `GEMVCFrameworkAIAssist.jsonc`: Framework-specific AI assistance
  - `GEMVCFrameworkAPIReference.json`: Framework API documentation
  - `FrameworkREADME.md`: This file

### AI Tool Support
- **Cursor**: Full context-aware code completion
- **GitHub Copilot**: Intelligent code suggestions
- **Other AI Tools**: Compatible with modern AI assistants

## 🏗️ Framework Architecture

```php
// Clean layered architecture
Frontend Request
    → Service (Authentication & Validation)
        → Controller (Business Logic)
            → Model (Data Logic & Response)
                → Table (Database Operations)
```

## 🎯 Key Features

### Type-Safe Development
```php
class ClassroomModel extends ClassroomTable {
    /** @var array<PlannedTeacherModel> */
    public array $_classroom_teachers;
    
    public function getWithTeachers(): self {
        $classroom = $this->findOrFail($this->id);
        $classroom->_classroom_teachers = (new PlannedTeacherModel())
            ->byClassroom($classroom->id);
        return $classroom;
    }
}
```

### Smart Model Properties
```php
class UserModel extends UserTable {
    // Database columns
    public int $id;
    public string $name;
    
    // Non-database properties (note the underscore)
    /** @var array<RoleModel> */
    public array $_roles;
}
```

### Clean Service Layer
```php
class UserService extends AuthService {
    public function create(): JsonResponse {
        $this->auth->authorize(['admin']);
        $this->validatePosts(['name' => 'string']);
        return (new UserController($this->request))->create();
    }
}
```

## 🚀 GEMVC

Transform your PHP development with GEMVC - where security meets simplicity! Build professional, secure APIs in minutes, not hours.

```php
// From complex, error-prone code...
$stmt = $pdo->prepare("SELECT u.id, u.name FROM users WHERE status = ?");
$stmt->execute(['active']);

// To elegant, secure simplicity! 😍
$users = QueryBuilder::select('u.id', 'u.name')
    ->from('users')
    ->whereEqual('status', 'active')
    ->run($pdoQuery);
```

## 🌟 Why GEMVC Stands Out

### 🛡️ Bank-Grade Security, Zero Effort
```php
// Automatic protection against:
// ✓ SQL Injection
// ✓ XSS Attacks
// ✓ Path Traversal
// ✓ Shell Injection
// ✓ File Upload Vulnerabilities

// Military-grade file encryption in just 3 lines!
$file = new FileHelper($_FILES['upload']['tmp_name'], 'secure/file.dat');
$file->secret = $encryptionKey;
$file->moveAndEncrypt();  // AES-256-CBC + HMAC verification 🔐
```

### 🤖 AI-Ready Framework
- **Dual AI Support**: 
  - `AIAssist.jsonc`: Real-time AI coding assistance
  - `GEMVCLibraryAPIReference.json`: Comprehensive API documentation
- **Smart Code Completion**: AI tools understand our library structure
- **Intelligent Debugging**: Better error analysis and fixes
- **Future-Ready**: Ready for emerging AI capabilities

### ⚡ Lightning-Fast Development
```php
// Modern image processing in one line
$image = new ImageHelper($uploadedFile)->convertToWebP(80);

// Clean API responses
$response = new JsonResponse()->success($data)->show();

// Type-safe database queries
QueryBuilder::select('id', 'name')
    ->from('users')
    ->whereEqual('status', 'active')
    ->limit(10)
    ->run($pdoQuery);
```

### 🎈 Lightweight & Flexible
- **Minimal Dependencies**: Just 3 core packages
- **Zero Lock-in**: No rigid rules or forced patterns
- **Cherry-Pick Features**: Use only what you need
- **Framework Agnostic**: Works with any PHP project

## 🔥 Installation Options

### 1. Complete Project Setup
```bash
# Create a new project with full architecture
composer create-project gemvc/installer your_project_name
```
This will create a new project with the latest version (v5.9.14) and set up the complete directory structure.

### 2. Framework Only
```bash
# Add GEMVC Framework to an existing project
composer require gemvc/framework
```
This installs only the framework without the project structure.

### 3. Configure Your Magic
```env
# Required .env configuration in your app folder:
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_db
DB_CHARSET=utf8
DB_USER=root
DB_PASSWORD=''

# Security Settings
TOKEN_SECRET='your_secret'
TOKEN_ISSUER='MyCompany'
REFRESH_TOKEN_VALIDATION_IN_SECONDS=43200
ACCESS_TOKEN_VALIDATION_IN_SECONDS=1200

# URL Configuration
SERVICE_IN_URL_SECTION=2
METHOD_IN_URL_SECTION=3
```

### 4. Initialize Your Application
```php
// index.php
load(__DIR__.'/app/.env');
$webserver = new ApacheRequest();
$bootstrap = new Bootstrap($webserver->request);
```

🔗 **Official Repository:** [gemFramework on GitHub](https://github.com/secure73/gemFramework)

## 🚀 Quick Start

### 1. Configure Your Magic
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=your_db
DB_USER=root
DB_PASSWORD='yourPassword'

# Security Settings
TOKEN_SECRET='your_secret'
TOKEN_ISSUER='your_api'
```

### 2. Start Building
```php
// Create an API endpoint
class Classroom extends APIService {
   public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    public function create(): JsonResponse
    {
        $this->auth->authorize(['company-admin']);
        $this->validatePostWithCompany(['name'=>'string','course_id'=>'int','subject_id'=>'int','start_date'=>'date','end_date'=>'date','?  location'=>'string','?description'=>'string']);
        return (new ClassroomController($this->request))->create();
    }
}
```

# 🚀 Quick Start

### Project Initialization
```php
// index.php
require_once 'vendor/autoload.php';

use Gemvc\Core\Bootstrap;
use Gemvc\Http\ApacheRequest;
use Gemvc\Http\NoCors;
use Symfony\Component\Dotenv\Dotenv;

// Configure CORS
NoCors::NoCors();

// Load environment configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/app/.env');

// Initialize framework
$webserver = new ApacheRequest();
$bootstrap = new Bootstrap($webserver->request);
```

### Required Configuration
Make sure your `app/.env` file contains:
```env
DB_HOST=localhost
DB_NAME=your_db
DB_USER=root
DB_PASSWORD=secret
```
```

## 💪 Core Features

### 🏗️ Modern Architecture
- **Type Safety**: PHP 8.0+ features
- **Modular Design**: Clear separation of concerns
- **Smart Patterns**: Factory, Builder, Traits
- **Clean Structure**: Intuitive organization

### 🛡️ Security Features
- **Input Sanitization**: Automatic XSS prevention
- **Query Protection**: SQL injection prevention
- **File Security**: Path traversal protection
- **Email Safety**: Content security validation

### 🎯 Developer Tools
- **Query Builder**: Intuitive database operations
- **File Processing**: Secure file handling with encryption
- **Image Handling**: WebP conversion and optimization
- **Type System**: Comprehensive validation

### ⚡ Performance
- **Connection Pooling**: Smart database connections
- **Resource Management**: Efficient file streaming
- **Memory Optimization**: Smart image processing
- **Query Optimization**: Built-in performance features

## 📋 Requirements
- PHP 8.0+
- PDO Extension
- OpenSSL Extension
- GD Library

## 🎯 Perfect For
- **Microservices**: Specific, efficient functionality
- **Legacy Projects**: Add modern features
- **New Projects**: Full control from day one
- **Learning**: Clear, understandable code

## 📚 Documentation Links
- [Framework Documentation](Document.md)
- [Library Documentation](../library/Documentation.md)
- [AI Integration Guide](AIIntegration.md)

## 🔗 Related Projects
- [GEMVC Library](https://github.com/secure73/gemvc)
- [Framework Examples](https://github.com/secure73/gemvc-examples)

## About
**Author:** Ali Khorsandfard <ali.khorsandfard@gmail.com>  
**GitHub:** [secure73/gemvc](https://github.com/secure73/gemvc)  
**License:** MIT

---
*Made with ❤️ for developers who love clean, secure, and efficient code.*

*Built with GEMVC Library v3.27.8 - Making PHP development secure and enjoyable!*
