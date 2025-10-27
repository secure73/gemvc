# Use our pre-built base image
FROM secure73/gemvc-swoole-base-alpine:latest

# Set labels
LABEL maintainer="GEMVC Team" \
      version="4.2-alpine" \
      description="Official GEMVC Framework Swoole Alpine Image"

# Set working directory
WORKDIR /var/www/html

# Copy the rest of the application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader \
    && composer dump-autoload -o \
    && composer clear-cache

# Expose port
EXPOSE 9501

# Add healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:9501/ || exit 1

# Start OpenSwoole server
CMD ["php", "index.php"]