FROM node:20-bookworm-slim AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.3-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev libicu-dev libonig-dev libpq-dev libxml2-dev libzip-dev \
    && docker-php-ext-install bcmath curl intl mbstring opcache pdo_pgsql pgsql xml zip \
    && a2enmod headers rewrite \
    && sed -i 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build ./public/build

COPY deploy/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["sh", "-c", "php artisan config:cache && exec apache2-foreground"]
