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
RUN npm run build

# ── Stage 2: PHP-FPM + Nginx ───────────────────────────────
FROM php:8.2-fpm-bullseye

# Sistem bağımlılıkları + nginx
RUN apt-get update && apt-get install -y \
    nginx \
    git curl zip unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev libxml2-dev libzip-dev libonig-dev \
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

# Frontend build çıktısını kopyala
COPY --from=frontend /app/public/build ./public/build

# PHP bağımlılıkları
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Storage klasörleri
RUN mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

# Nginx site konfigürasyonu (APP_PORT = placeholder, runtime'da değiştirilir)
RUN cat > /etc/nginx/sites-enabled/default << 'NGINXEOF'
server {
    listen APP_PORT;
    root /app/public;
    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param HTTPS on;
        fastcgi_param HTTP_X_FORWARDED_PROTO https;
        fastcgi_param PHP_VALUE "upload_max_filesize=64M \n post_max_size=64M";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXEOF

# Entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000

CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    /entrypoint.sh
