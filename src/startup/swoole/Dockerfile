FROM openswoole/swoole:php8.2-alpine

# Install additional PHP extensions
RUN apk add --no-cache \
    pdo_mysql \
    pdo_pgsql \
    redis \
    && docker-php-ext-install pdo_mysql pdo_pgsql

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port
EXPOSE 9501

# Start OpenSwoole server
CMD ["php", "index.php"]
