# Installation Guide

## Requirements

Before installing GEMVC, ensure your system meets these requirements:

- PHP 8.0 or higher
- PDO Extension
- OpenSSL Extension
- GD Library
- Composer (for package management)
- OpenSwoole Extension (optional, for high-performance server)
- Redis Extension (optional, for WebSocket scaling)

## Installation Steps

### 1. Install via Composer

```bash
composer require gemvc/library
```

### 2. Initialize Your Project

After installing the library, initialize your project with:

```bash
vendor/bin/gemvc init
```

This command will:
- Create the necessary directory structure:
  - `/app/api` - API endpoints
  - `/app/controller` - Controllers
  - `/app/model` - Models
  - `/app/table` - Database tables
- Generate a sample `.env` file
- Set up local command wrappers
- Configure your server (Apache or OpenSwoole)

During initialization, you'll be prompted to choose your server:
```
Choose your server:
[0] Apache
[1] OpenSwoole
```

### 3. Configure Your Environment

Create or modify your `.env` file with these essential settings:

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

## Verification

To verify your installation:

1. Check the installation:
```bash
vendor/bin/gemvc --version
```

2. Run the installation test:
```bash
vendor/bin/gemvc test:install
```

## Next Steps

- [Configuration Guide](configuration.md)
- [Quick Start Guide](quick-start.md)
- [Core Features](../features/README.md)

## Troubleshooting

### Common Issues

1. **Composer Installation Fails**
   - Ensure you have the latest Composer version
   - Check PHP version compatibility
   - Verify PHP extensions are installed

2. **Project Initialization Issues**
   - Check directory permissions
   - Verify PHP CLI is available
   - Ensure all required extensions are loaded

3. **Server Setup Problems**
   - For Apache: Check mod_rewrite is enabled
   - For OpenSwoole: Verify extension installation
   - Check port availability

### Getting Help

If you encounter any issues:
- Check the [GitHub Issues](https://github.com/secure73/gemvc/issues)
- Review the [Troubleshooting Guide](../guides/troubleshooting.md)
- Join our [Discord Community](https://discord.gg/gemvc) 