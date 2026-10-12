# syntax=docker/dockerfile:1

# 1) Build-ul frontend-ului Vue (repo separat)
FROM node:22-alpine AS frontend
ARG FRONTEND_REF=main
ADD https://github.com/rmsddd/gym-weight-tracker-frontend.git#${FRONTEND_REF} /app
WORKDIR /app
# --legacy-peer-deps: oxlint si eslint-plugin-oxlint cer versiuni diferite ca peer dependency
RUN npm ci --legacy-peer-deps && npm run build

# 2) Laravel + Apache, care serveste si build-ul de Vue din public/
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && docker-php-ext-install pdo_mysql opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=frontend /app/dist/ public/

RUN composer dump-autoload --optimize --no-dev \
    && sed -i 's/\r$//' docker/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=mysql

CMD ["sh", "docker/start.sh"]
