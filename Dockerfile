# syntax=docker/dockerfile:1
# Imagen de producción para Railway / Render (PHP-FPM + Nginx en el puerto 8080).

# 1) Dependencias PHP (sin las de desarrollo)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# 2) Assets con Vite (Flux importa su CSS desde vendor/)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# 3) Imagen final
FROM serversideup/php:8.5-fpm-nginx-v5.1.2

USER root
RUN install-php-extensions intl
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/

USER www-data
WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction

# Al arrancar: migraciones y cachés de Laravel (automatizaciones de la imagen base).
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off

EXPOSE 8080
