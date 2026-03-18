#!/bin/sh
set -e

# Nginx config'e gerçek PORT değerini yaz
REAL_PORT=${PORT:-8000}
sed -i "s/APP_PORT/$REAL_PORT/g" /etc/nginx/sites-enabled/default

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

echo "==> Creating admin user with proper bcrypt hash..."
php artisan tinker --execute="
\$user = App\Models\User::firstOrNew(['email' => 'admin@cafequiz.com']);
\$user->name = 'Admin';
\$user->email_verified_at = now();
\$user->password = bcrypt('CafeAdmin2026!');
\$user->save();
echo 'Admin user ready.' . PHP_EOL;
"

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx on port $REAL_PORT..."
exec nginx -g 'daemon off;'
