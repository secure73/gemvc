# Use our pre-built base image
FROM secure73/gemvc-swoole-base-alpine:4.0

# Set labels
LABEL maintainer="GEMVC Team" \
      version="4.0-alpine" \
      description="Official GEMVC Framework Swoole Alpine Image"


# Copy application files
COPY . .

# Install remaining dependencies
RUN composer install --no-dev --optimize-autoloader \
    && composer clear-cache \
    && chmod -R 550 /var/www/html \
    && find /var/www/html/app -type d -exec chmod 550 {} \; \
    && find /var/www/html/app -type f -exec chmod 440 {} \;

# Expose port
EXPOSE 9501

# Start OpenSwoole server
CMD ["php", "index.php"]