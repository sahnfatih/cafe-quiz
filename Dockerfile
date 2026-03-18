# ── Stage 1: Frontend Build (Node.js) ─────────────────────
FROM node:20-bullseye-slim AS frontend

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ── Stage 2: PHP Application ───────────────────────────────
FROM php:8.2-cli-bullseye

# Sistem bağımlılıkları
RUN apt-get update && apt-get install -y \
    git curl zip unzip gnupg \
    libpng-dev libpq-dev libxml2-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP eklentileri (PostgreSQL dahil)
RUN docker-php-ext-install pdo pdo_pgsql mbstring xml bcmath gd pcntl zip

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
