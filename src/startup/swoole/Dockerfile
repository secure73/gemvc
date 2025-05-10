FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    libssl-dev \
    libnghttp2-dev \
    git \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install OpenSwoole
RUN pecl install openswoole \
    && docker-php-ext-enable openswoole

# Install PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a basic composer.json file if it doesn't exist
RUN echo '{"name":"gemvc/app","type":"project","require":{"gemvc/framework":"^5.9"},"autoload":{"psr-4":{"App\\\\Api\\\\":"app/api/","App\\\\Controller\\\\":"app/controller/","App\\\\Model\\\\":"app/model/","App\\\\Table\\\\":"app/table/"}}}' > composer.json

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application files
COPY . .

# Configure PHP
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 64M/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/post_max_size = 8M/post_max_size = 64M/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/memory_limit = 128M/memory_limit = 256M/' "$PHP_INI_DIR/php.ini"

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port
EXPOSE 9501

# Start OpenSwoole server
CMD ["php", "index.php"]