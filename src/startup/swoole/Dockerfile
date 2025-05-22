# Use our pre-built base image
FROM secure73/gemvc-swoole-base-alpine:latest

# Set labels
LABEL maintainer="GEMVC Team" \
      version="4.2-alpine" \
      description="Official GEMVC Framework Swoole Alpine Image"

# Copy application files
COPY . .

# Install remaining dependencies
RUN composer install --no-dev --optimize-autoloader \
    && composer clear-cache

# Expose port
EXPOSE 9501

# Start OpenSwoole server
CMD ["php", "index.php"]