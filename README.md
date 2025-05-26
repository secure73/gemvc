# 🚀 GEMVC

Transform your PHP development with GEMVC - a modern PHP framework where security meets simplicity! Build professional, secure APIs in minutes, not hours.

## 📋 Table of Contents
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Development Environment](#-development-environment)
- [Core Features](#-core-features)
- [Documentation](#-documentation)
- [Requirements](#-requirements)
- [About](#about)

## 🔥 Installation

```bash
composer require gemvc/library
```

### Initialize Your Project

```bash
# Initialize a new GEMVC project
vendor/bin/gemvc init
```

This will:
- Create the necessary directory structure
- Generate a sample `.env` file
- Set up local command wrappers

## 🔄 Quick Start

### 1. Development Environment Setup

GEMVC comes with a complete Docker development environment that includes:
- PHP server (Apache or OpenSwoole)
- MySQL 8.0 database
- PHPMyAdmin for easy database management

To start the development environment:

```bash
# Start all services
docker-compose up --build
```

This will start:
- Web server on port 9501 for swoole or 80 for apache depends on init selected option
- MySQL database on port 3306
- PHPMyAdmin on port 8080
- Redis 

#### Database Access
- **PHPMyAdmin**: http://localhost:8080
  - Username: `root`
  - Password: `rootpassword`
- **MySQL Direct Access**:
  - Host: `localhost`
  - Port: `3306`
  - Username: `root`
  - Password: `rootpassword`

### 2. Basic Configuration

Create an `.env` file in your app directory:

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

### 3. Create Your First API

```php
namespace App\Api;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use Gemvc\Core\RedisManager;

class User extends ApiService {
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    
    public function getUsers():JsonResponse {
        if(!$this->request->auth(['admin'])) {
            return $this->request->returnResponse();
        }
        $redis = new RedisManager::getInstance();
        $redis_key = md5($this->request->requestedUrl);
        $response = $redis->getJsonResponse($redis_key);
        if(!$response)
        {
            $response = (new UserController($this->request))->list();
            //cach for 10 minutes 600 seconds
            $redis->setJsonResponse($redis_key,$response,time()+600);
            return $response;
        }
        return $response;
    }
}
```

### 4. Access API Documentation

Visit `yourdomain/index/document` to access the interactive API documentation. The documentation is automatically generated from your API service classes and includes:
- Endpoint details
- Request/response examples
- Parameter documentation
- Postman collection export

## 🎯 Core Features

- **Modern Architecture**: Type-safe, modular design with clean structure
- **Swoole Ready**: Seamlessly switch between OpenSwoole and Apache servers - your code works identically on both without any modifications
- **Dual Server Support**: Works with both Apache and OpenSwoole
- **Security First**: Built-in JWT authentication, input sanitization, and protection
- **Database Abstraction**: Type-safe queries, ORM capabilities, and table generation
- **Real-time Ready**: WebSocket support with Redis scaling
- **Developer Experience**: CLI tools, code generation, and comprehensive documentation
- **Database Connection Pooling**: Optimized connection management for maximum performance and resource efficiency
- **Built-in ORM**: Powerful query builder and ORM with intuitive CRUD operations
- **Built-in Redis Support**: Seamless Redis integration for caching and real-time features
- **Auto Documentation**: Interactive API documentation with Postman export
- **AI Ready**: Built-in APIs and interfaces optimized for AI assistant integration and automation


## 📚 Documentation

For a comprehensive overview of all components, features, and architecture, please refer to our [Documentation.md](Documentation.md)

## 📋 Requirements
- PHP 8.0+
- PDO Extension
- OpenSSL Extension
- GD Library
- OpenSwoole Extension (optional)
- Redis Extension (optional)
- Docker and Docker Compose (for development environment)

## About
**Author:** Ali Khorsandfard <ali.khorsandfard@gmail.com>  
**GitHub:** [secure73/gemvc](https://github.com/secure73/gemvc)  
**License:** MIT

---
*Made with ❤️ for developers who love clean, secure, and efficient code.*

