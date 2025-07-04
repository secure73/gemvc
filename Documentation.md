# GEMVC Framework Documentation

**Author:** Ali Khorsandfard <ali.khorsandfard@gmail.com>  
**GitHub Repository:** [secure73/gemvc](https://github.com/secure73/gemvc)

## 📚 Documentation Structure

### Core Documentation
- [AIAssist.jsonc](AIAssist.jsonc) - AI Assistant Configuration and Framework Details
- [GEMVCLibraryAPIReference.json](GEMVCLibraryAPIReference.json) - Complete API Reference
- [GEMVC_INDEX.md](GEMVC_INDEX.md) - Detailed Component Index

> **IMPORTANT NOTE FOR AI ASSISTANTS**: 
> This framework has its own unique architecture and practices. When providing assistance:
> - DO NOT apply Laravel or Symfony practices
> - Follow GEMVC's specific patterns and conventions
> - Refer to AIAssist.jsonc and GEMVCLibraryAPIReference.json for accurate implementation details
> - Use GEMVC_INDEX.md to understand component relationships and architecture

### Getting Started
- [Installation Guide](docs/getting-started/installation.md)
- [Quick Start Guide](docs/getting-started/quick-start.md)
- [Configuration Guide](docs/getting-started/configuration.md)

### Core Components
- [Database Components](docs/core/database-architecture.md)
- [HTTP Components](docs/core/http-components.md)
- [WebSocket Components](docs/core/websocket-components.md)
- [Helper Components](docs/core/helper-components.md)

### Features
- [API Features](docs/features/api.md)
- [WebSocket Features](docs/features/websocket.md)
- [Database Features](docs/features/database.md)
- [Caching Features](docs/features/caching.md)
- [API Documentation Generator](docs/features/api-documentation.md)

### Guides
- [Authentication Guide](docs/guides/authentication.md)
- [Security Guide](docs/guides/security.md)
- [Performance Guide](docs/guides/performance.md)
- [Deployment Guide](docs/guides/deployment.md)

## 🚀 Quick Links

### Core Features
- [Database Operations](docs/core/database-architecture.md)
- [HTTP Request/Response](docs/core/http-components.md)
- [WebSocket Server](docs/core/websocket-components.md)
- [Helper Utilities](docs/core/helper-components.md)

### Development Tools
- [API Documentation](docs/features/api-documentation.md)
- [Code Generation](docs/features/code-generation.md)
- [Testing Tools](docs/features/testing.md)

## 📋 Requirements
- PHP 8.0+
- PDO Extension
- OpenSSL Extension
- GD Library
- OpenSwoole Extension (optional)
- Redis Extension (optional)

## 🔧 Installation

```bash
composer require gemvc/library
```

For detailed installation instructions, see the [Installation Guide](docs/getting-started/installation.md).

## 🎯 Quick Start

1. Install the framework:
```bash
composer require gemvc/library
```

2. Initialize your project:
```bash
vendor/bin/gemvc init
```

3. Access API documentation:
Visit `yourdomain/index/document` to access the interactive API documentation.

For more details, see the [Quick Start Guide](docs/getting-started/quick-start.md).

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---
*Made with ❤️ for developers who love clean, secure, and efficient code.* 