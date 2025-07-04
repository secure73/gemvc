# Deployment Guide

## Overview

This guide covers deploying GEMVC applications to various environments, from development to production. GEMVC supports multiple deployment strategies including traditional web servers, containerized deployments, and cloud platforms.

## Deployment Strategies

### 1. Traditional Web Server Deployment

#### Apache Deployment
```bash
# Install Apache and PHP modules
sudo apt-get update
sudo apt-get install apache2 php8.1 php8.1-mysql php8.1-openssl php8.1-gd

# Configure Apache virtual host
sudo nano /etc/apache2/sites-available/gemvc-app.conf
```

**Apache Virtual Host Configuration:**
```apache
<VirtualHost *:80>
    ServerName your-app.com
    ServerAdmin webmaster@your-app.com
    DocumentRoot /var/www/gemvc-app/public
    
    <Directory /var/www/gemvc-app/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/gemvc-app_error.log
    CustomLog ${APACHE_LOG_DIR}/gemvc-app_access.log combined
</VirtualHost>
```

#### Nginx Deployment
```bash
# Install Nginx and PHP-FPM
sudo apt-get update
sudo apt-get install nginx php8.1-fpm php8.1-mysql php8.1-openssl php8.1-gd
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name your-app.com;
    root /var/www/gemvc-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 2. OpenSwoole Deployment

#### Production OpenSwoole Server
```php
<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

use Gemvc\Core\SwooleBootstrap;

$bootstrap = new SwooleBootstrap();
$bootstrap->run();
```

**OpenSwoole Configuration:**
```php
<?php
// config/swoole.php

return [
    'host' => '0.0.0.0',
    'port' => 9501,
    'mode' => SWOOLE_PROCESS,
    'sock_type' => SWOOLE_SOCK_TCP,
    'settings' => [
        'worker_num' => 4,
        'max_request' => 10000,
        'enable_static_handler' => true,
        'document_root' => __DIR__ . '/../public',
        'log_level' => SWOOLE_LOG_ERROR,
        'pid_file' => __DIR__ . '/../storage/swoole.pid',
        'log_file' => __DIR__ . '/../storage/swoole.log',
    ]
];
```

**Systemd Service:**
```ini
# /etc/systemd/system/gemvc-app.service

[Unit]
Description=GEMVC Application
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/gemvc-app
ExecStart=/usr/bin/php public/index.php
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### 3. Docker Deployment

#### Dockerfile
```dockerfile
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install OpenSwoole
RUN pecl install openswoole && docker-php-ext-enable openswoole

# Set working directory
WORKDIR /var/www

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www

# Expose port
EXPOSE 9501

# Start application
CMD ["php", "public/index.php"]
```

#### Docker Compose
```yaml
# docker-compose.yml

version: '3.8'

services:
  app:
    build: .
    ports:
      - "9501:9501"
    volumes:
      - .:/var/www
      - ./storage:/var/www/storage
    environment:
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_NAME=gemvc_app
      - DB_USER=gemvc_user
      - DB_PASSWORD=gemvc_password
      - REDIS_HOST=redis
      - REDIS_PORT=6379
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: gemvc_app
      MYSQL_USER: gemvc_user
      MYSQL_PASSWORD: gemvc_password
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - app

volumes:
  mysql_data:
  redis_data:
```

### 4. Cloud Platform Deployment

#### AWS Deployment

**EC2 Instance Setup:**
```bash
# Launch EC2 instance
aws ec2 run-instances \
    --image-id ami-0c02fb55956c7d316 \
    --instance-type t3.medium \
    --key-name your-key-pair \
    --security-group-ids sg-xxxxxxxxx \
    --subnet-id subnet-xxxxxxxxx
```

**AWS CodeDeploy:**
```yaml
# appspec.yml

version: 0.0
os: linux
files:
  - source: /
    destination: /var/www/gemvc-app
hooks:
  BeforeInstall:
    - location: scripts/before_install.sh
      timeout: 300
      runas: root
  AfterInstall:
    - location: scripts/after_install.sh
      timeout: 300
      runas: root
  ApplicationStart:
    - location: scripts/start_application.sh
      timeout: 300
      runas: root
```

#### Google Cloud Platform

**App Engine Configuration:**
```yaml
# app.yaml

runtime: php81
env: flex

automatic_scaling:
  target_cpu_utilization: 0.65
  min_instances: 1
  max_instances: 10

resources:
  cpu: 1
  memory_gb: 0.5
  disk_size_gb: 10

env_variables:
  DB_HOST: /cloudsql/PROJECT_ID:REGION:INSTANCE_NAME
  DB_NAME: gemvc_app
  DB_USER: gemvc_user
  DB_PASSWORD: gemvc_password
```

## Environment Configuration

### Production Environment Variables
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.com

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=gemvc_app
DB_USER=gemvc_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=redis_password

# Security
TOKEN_SECRET=your_very_secure_secret_key
TOKEN_ISSUER=your_app_name

# WebSocket (if using)
WS_HOST=0.0.0.0
WS_PORT=9501
WS_SSL_ENABLED=true
WS_SSL_CERT=/path/to/cert.pem
WS_SSL_KEY=/path/to/key.pem
```

### SSL Configuration

#### Let's Encrypt Setup
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache

# Obtain SSL certificate
sudo certbot --apache -d your-app.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

#### Manual SSL Setup
```bash
# Generate private key
openssl genrsa -out private.key 2048

# Generate certificate signing request
openssl req -new -key private.key -out certificate.csr

# Generate self-signed certificate (for testing)
openssl x509 -req -days 365 -in certificate.csr -signkey private.key -out certificate.crt
```

## Performance Optimization

### Database Optimization
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at);

-- Optimize table structure
OPTIMIZE TABLE users;
ANALYZE TABLE users;
```

### Redis Configuration
```conf
# /etc/redis/redis.conf

maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### PHP Optimization
```ini
# /etc/php/8.1/fpm/php.ini

memory_limit = 256M
max_execution_time = 30
upload_max_filesize = 10M
post_max_size = 10M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
```

## Monitoring and Logging

### Application Logging
```php
<?php
// config/logging.php

return [
    'default' => 'file',
    'channels' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('logs/app.log'),
            'level' => 'debug',
        ],
        'error' => [
            'driver' => 'file',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
        ],
    ],
];
```

### Health Checks
```php
<?php
// public/health.php

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'services' => [
        'database' => checkDatabase(),
        'redis' => checkRedis(),
        'disk' => checkDiskSpace(),
    ]
];

echo json_encode($health);

function checkDatabase(): bool
{
    try {
        $pdo = new PDO(
            "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD']
        );
        return $pdo->query('SELECT 1')->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

function checkRedis(): bool
{
    try {
        $redis = new Redis();
        $redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);
        return $redis->ping() === '+PONG';
    } catch (Exception $e) {
        return false;
    }
}

function checkDiskSpace(): bool
{
    $freeSpace = disk_free_space('/');
    $totalSpace = disk_total_space('/');
    $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
    
    return $usagePercent < 90;
}
```

## Backup Strategies

### Database Backup
```bash
#!/bin/bash
# scripts/backup_database.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/database"
DB_NAME="gemvc_app"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/backup_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete

echo "Database backup completed: backup_$DATE.sql.gz"
```

### File Backup
```bash
#!/bin/bash
# scripts/backup_files.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/files"
APP_DIR="/var/www/gemvc-app"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create file backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C $APP_DIR .

# Remove backups older than 7 days
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +7 -delete

echo "File backup completed: files_$DATE.tar.gz"
```

## Security Considerations

### Firewall Configuration
```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 9501/tcp  # If using OpenSwoole
sudo ufw enable

# iptables (CentOS/RHEL)
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 9501 -j ACCEPT
```

### SSL/TLS Security
```nginx
# nginx.conf

ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
```

## Troubleshooting

### Common Issues

#### Database Connection Issues
```bash
# Check database connectivity
mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD -e "SELECT 1"

# Check database logs
sudo tail -f /var/log/mysql/error.log
```

#### Memory Issues
```bash
# Check memory usage
free -h

# Check PHP memory limit
php -i | grep memory_limit

# Check process memory usage
ps aux --sort=-%mem | head -10
```

#### Performance Issues
```bash
# Check CPU usage
top

# Check disk I/O
iotop

# Check network connections
netstat -tulpn
```

## Next Steps

- [Performance Guide](performance.md)
- [Security Guide](security.md)
- [Getting Started](../getting-started/quick-start.md) 