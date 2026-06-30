#!/usr/bin/env bash

set -e

# Jika ada argument (misal: php artisan queue:work), jalankan langsung tanpa migrasi
if [ $# -gt 0 ]; then
    exec "$@"
fi

echo "Running migrations..."
for i in {1..15}; do
    if php artisan migrate --force; then
        echo "Migrations completed successfully."
        break
    else
        echo "Database not ready yet, waiting 2 seconds..."
        sleep 2
    fi
done

echo "Starting PHP-FPM..."
exec php-fpm
