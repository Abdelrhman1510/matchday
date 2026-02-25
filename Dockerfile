FROM php:8.3-cli-bookworm

# System dependencies (libonig-dev required by mbstring)
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libxml2-dev libzip-dev libicu-dev \
    libonig-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions (including gd)
RUN docker-php-ext-install \
    pdo pdo_mysql mbstring xml bcmath gd zip fileinfo intl

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy full source
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build assets
RUN npm install && npm run build

# Symlink storage
RUN php artisan storage:link 2>/dev/null || true

EXPOSE 8000

# At runtime: cache config (env vars available), migrate, seed, serve
CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan migrate --force \
    && php artisan db:seed --force 2>/dev/null; \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
