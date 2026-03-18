# ── Stage 1: Frontend Build (Node.js) ─────────────────────
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

# ── Stage 2: PHP Application ───────────────────────────────
FROM php:8.2-cli-bullseye

# Sistem bağımlılıkları (gd için gerekli tüm kütüphaneler dahil)
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev libxml2-dev libzip-dev \
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
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
