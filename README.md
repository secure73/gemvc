# 🚀 GEMVC

Transform your PHP development with GEMVC - a modern PHP framework where security meets simplicity! Build professional, secure APIs in minutes, not hours.

## 📋 Table of Contents
- [Installation](#-installation)
- [Quick Start](#-quick-start)
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

### 1. Basic Configuration

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

### 2. Create Your First API

```php
namespace App\Api;

use Gemvc\Core\ApiService;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;

class User extends ApiService {
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }
    
    public function getUsers():JsonResponse {
        if(!$this->request->auth(['admin'])) {
            return $this->request->returnResponse();
        }
        
        return (new UserController($this->request))->list();
    }
}
```

## 🎯 Core Features

- **Modern Architecture**: Type-safe, modular design with clean structure
- **Dual Server Support**: Works with both Apache and OpenSwoole
- **Security First**: Built-in JWT authentication, input sanitization, and protection
- **Database Abstraction**: Type-safe queries, ORM capabilities, and table generation
- **Real-time Ready**: WebSocket support with Redis scaling
- **Developer Experience**: CLI tools, code generation, and comprehensive documentation

## 📚 Documentation

For a comprehensive overview of all components, features, and architecture, please refer to our [GEMVC Framework Index](GEMVC_INDEX.md)

## 📋 Requirements
- PHP 8.0+
- PDO Extension
- OpenSSL Extension
- GD Library
- OpenSwoole Extension (optional)
- Redis Extension (optional)

## About
**Author:** Ali Khorsandfard <ali.khorsandfard@gmail.com>  
**GitHub:** [secure73/gemvc](https://github.com/secure73/gemvc)  
**License:** MIT

---
*Made with ❤️ for developers who love clean, secure, and efficient code.*

