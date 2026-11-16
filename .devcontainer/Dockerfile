FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm
USER root
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl libcurl4-openssl-dev libicu-dev libonig-dev libpq-dev libxml2-dev libzip-dev postgresql-client \
    && docker-php-ext-install pdo_pgsql pgsql bcmath curl intl mbstring xml zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs
