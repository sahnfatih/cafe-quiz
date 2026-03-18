FROM php:8.2-cli

# Sistem bağımlılıkları
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libpq-dev libxml2-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP eklentileri (PostgreSQL dahil)
RUN docker-php-ext-install pdo pdo_pgsql mbstring xml bcmath gd pcntl zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Uygulama dosyaları
COPY . .

# PHP bağımlılıkları
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Frontend build
RUN npm ci && npm run build

# Storage ve cache klasörleri
RUN mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# Başlangıç komutu
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
