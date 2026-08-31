#!/bin/bash
set -e

# Run composer install if vendor doesn't exist
if [ ! -d "vendor" ]; then
    composer install --no-interaction --optimize-autoloader --no-dev
fi

# Run migrations
php artisan migrate --force

# Link storage
php artisan storage:link || true

# Execute the main command
exec "$@"
