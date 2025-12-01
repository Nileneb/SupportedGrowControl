# Production Dockerfile for Growdash (Laravel)
# Multi-stage: build assets, then serve PHP app

FROM node:18-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* yarn.lock* ./
RUN npm ci || (echo "No lockfile, falling back" && npm install)
COPY resources ./resources
COPY vite.config.js ./vite.config.js
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

# System deps
RUN apk add --no-cache bash git libzip-dev oniguruma-dev curl icu-dev zlib-dev

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring zip intl

# Copy app
COPY . .

# Copy built assets
COPY --from=frontend /app/resources /var/www/html/resources
COPY --from=frontend /app/dist /var/www/html/public/build

# Copy vendor
COPY --from=vendor /app/vendor /var/www/html/vendor

# Laravel optimize (runtime)
ENV APP_ENV=production
RUN php artisan key:generate || true \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache \
 && php artisan event:cache || true

CMD ["php-fpm"]
