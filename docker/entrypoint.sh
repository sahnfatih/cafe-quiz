#!/bin/sh
set -e

# Nginx config'e gerçek PORT değerini yaz
REAL_PORT=${PORT:-8000}
sed -i "s/APP_PORT/$REAL_PORT/g" /etc/nginx/sites-enabled/default

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx on port $REAL_PORT..."
exec nginx -g 'daemon off;'
