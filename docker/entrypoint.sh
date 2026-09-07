#!/bin/bash
set -e

# Ensure runtime-writable directories/permissions (mounted volumes may reset ownership)
mkdir -p storage/framework/{cache,sessions,views} storage/logs
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

if [ ! -f "storage/oauth-private.key" ]; then
    : # placeholder for future key generation steps if needed
fi

# Cache config/routes/views for production performance (safe to skip failures on first boot)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run pending migrations automatically (disable by setting SKIP_MIGRATIONS=true)
if [ "${SKIP_MIGRATIONS}" != "true" ]; then
    php artisan migrate --force || true
fi

# Ensure public/storage symlink exists
php artisan storage:link || true

exec "$@"
