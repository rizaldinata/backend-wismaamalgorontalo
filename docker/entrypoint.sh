#!/bin/sh
set -e

# Ensure storage directories exist (these might be empty because of host volume mounting)
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs

# Fix permissions so Laravel can write to them
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Create storage link if it doesn't exist
if [ ! -L /var/www/public/storage ]; then
    php artisan storage:link
fi

# Execute the main command (passed from CMD in Dockerfile)
exec "$@"
