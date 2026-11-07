#!/bin/sh
set -e

# Cache configuration and routes for every service that uses this image.
# Migrations are intentionally NOT run here: deploys run them explicitly so a
# broken migration cannot restart-loop the whole stack.
if [ -f /var/www/html/.env ] || [ -n "$APP_KEY" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

exec "$@"
