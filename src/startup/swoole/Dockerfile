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

# Copy and set up entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Verify vendor directory and permissions
RUN ls -la /var/www/html/vendor/symfony/dotenv \
    && php -r "require '/var/www/html/vendor/autoload.php';"

# Expose port
EXPOSE 9501

# Add healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:9501/ || exit 1

# Set entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]

# Start OpenSwoole server
CMD ["php", "index.php"]