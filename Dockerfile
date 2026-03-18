# ── Stage 1: Frontend Build ────────────────────────────────
FROM node:20-bullseye-slim AS frontend

ARG VITE_PUSHER_APP_KEY=6e9cc3f14f25a174f012
ARG VITE_PUSHER_APP_CLUSTER=eu
ENV VITE_PUSHER_APP_KEY=$VITE_PUSHER_APP_KEY
ENV VITE_PUSHER_APP_CLUSTER=$VITE_PUSHER_APP_CLUSTER

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build && echo "=== Build OK ===" && ls -la public/build/

# ── Stage 2: PHP-FPM + Nginx ───────────────────────────────
FROM php:8.2-fpm-bullseye

# Sistem bağımlılıkları + nginx + supervisor
RUN apt-get update && apt-get install -y \
    nginx supervisor \
    git curl zip unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev libxml2-dev libzip-dev libonig-dev \
    gettext-base \
    && rm -rf /var/lib/apt/lists/*

# PHP eklentileri
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql mbstring xml bcmath pcntl zip gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Uygulama dosyaları
COPY . .

# Frontend build çıktısını kopyala ve doğrula
COPY --from=frontend /app/public/build ./public/build
RUN echo "=== Verifying build ===" && find public/build -type f | head -20

# PHP bağımlılıkları
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Storage klasörleri
RUN mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

# Nginx konfigürasyonu (PORT değişkeni ile)
RUN cat > /etc/nginx/sites-available/default <<'NGINXCONF'
server {
    listen ${PORT};
    root /app/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXCONF

# Supervisor konfigürasyonu
RUN cat > /etc/supervisor/conf.d/app.conf <<'SUPERVISORCONF'
[supervisord]
nodaemon=true
logfile=/dev/null
logfile_maxbytes=0

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=/bin/sh -c "envsubst '$$PORT' < /etc/nginx/sites-available/default > /etc/nginx/sites-enabled/default && nginx -g 'daemon off;'"
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
SUPERVISORCONF

EXPOSE 8000

CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    PORT=${PORT:-8000} supervisord -c /etc/supervisor/conf.d/app.conf
