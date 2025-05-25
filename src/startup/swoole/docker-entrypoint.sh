#!/bin/sh
set -e

# Check if .env file exists in app directory
if [ ! -f /var/www/html/app/.env ]; then
    echo "Creating .env file from example..."
    cp /var/www/html/example.env /var/www/html/app/.env
fi

# Set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Start OpenSwoole server
exec "$@" 