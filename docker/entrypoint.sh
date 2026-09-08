#!/bin/bash
set -e

# Ensure runtime-writable directories/permissions (mounted volumes may reset ownership)
mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/private
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Generate DOKU RSA keypair on first boot only (skip if private.key already exists,
# e.g. mounted via a persistent volume) so the public key registered with DOKU
# never becomes invalid due to key regeneration on redeploys/restarts.
DOKU_KEY_DIR="storage/app/private"
if [ ! -f "${DOKU_KEY_DIR}/private.key" ]; then
    echo "Generating DOKU RSA keypair (first boot)..."
    openssl genrsa -out "${DOKU_KEY_DIR}/private.key" 2048
    openssl pkcs8 -topk8 -inform PEM -outform PEM -in "${DOKU_KEY_DIR}/private.key" -out "${DOKU_KEY_DIR}/pkcs8.key" -v1 PBE-SHA1-3DES -passout pass:
    openssl rsa -in "${DOKU_KEY_DIR}/private.key" -outform PEM -pubout -out "${DOKU_KEY_DIR}/public.pem"
    chown www-data:www-data "${DOKU_KEY_DIR}"/private.key "${DOKU_KEY_DIR}"/pkcs8.key "${DOKU_KEY_DIR}"/public.pem
    chmod 600 "${DOKU_KEY_DIR}"/private.key "${DOKU_KEY_DIR}"/pkcs8.key
    chmod 644 "${DOKU_KEY_DIR}"/public.pem
    echo "DOKU keys generated. Register storage/app/private/public.pem's content with DOKU Merchant Dashboard."
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
