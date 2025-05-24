# Use our pre-built base image
FROM secure73/gemvc-swoole-base-alpine:latest

# Set labels
LABEL maintainer="GEMVC Team" \
      version="4.2-alpine" \
      description="Official GEMVC Framework Swoole Alpine Image"

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader \
    && composer dump-autoload -o \
    && composer clear-cache

# Copy the rest of the application
COPY . .

# Verify vendor directory and permissions
RUN ls -la /var/www/html/vendor/symfony/dotenv \
    && php -r "require '/var/www/html/vendor/autoload.php';"

# Expose port
EXPOSE 9501

# Start OpenSwoole server
CMD ["php", "index.php"]