#!/bin/sh
set -e

# Ensure storage directories exist (these might be empty because of host volume mounting)
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs

# Fix permissions so Laravel can write to them
# We use 777 as a fallback for local laptop deployments but chown for standard practice
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Execute the main command (passed from CMD in Dockerfile)
exec "$@"
