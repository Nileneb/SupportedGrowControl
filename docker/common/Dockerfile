# Unified multi-stage Dockerfile to build consistent assets once

############################
# Stage: Composer builder  #
############################
FROM php:8.4-fpm AS composer-builder

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl unzip libpq-dev libonig-dev libssl-dev libxml2-dev libcurl4-openssl-dev \
    libicu-dev libzip-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql pdo_pgsql pgsql opcache intl zip bcmath soap pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www
COPY . /var/www

# Install Composer and dependencies as root (with --no-scripts to avoid permission issues)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist --no-scripts

# Create ALL required Laravel directories
RUN mkdir -p storage/framework/{sessions,views,cache,testing} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Set ownership to www-data FIRST, then permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

############################
# Stage: Node assets build #
############################
FROM node:20-alpine AS node-build
WORKDIR /var/www

# Build args for Vite environment variables AND APP_URL
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ARG APP_URL=https://grow.linn.games

# Make them available during build
ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME
ENV APP_URL=$APP_URL

# Lockfile-based deterministic install
COPY package.json package-lock.json vite.config.js /var/www/

# Copy app sources and vendor for Flux CSS processing
COPY resources/ /var/www/resources/
COPY public/ /var/www/public/
COPY composer.json composer.lock /var/www/
COPY artisan /var/www/
COPY bootstrap/ /var/www/bootstrap/
COPY config/ /var/www/config/
COPY routes/ /var/www/routes/
COPY app/ /var/www/app/
COPY --from=composer-builder /var/www/vendor /var/www/vendor

RUN npm ci && npm run build

# Publish Flux assets to public
RUN mkdir -p /var/www/public/flux \
    && cp -f /var/www/vendor/livewire/flux/dist/flux.min.js /var/www/public/flux/flux.min.js \
    && cp -f /var/www/vendor/livewire/flux/dist/flux-lite.min.js /var/www/public/flux/flux-lite.min.js \
    && cp -f /var/www/vendor/livewire/flux/dist/flux.css /var/www/public/flux/flux.css

################################
# Stage: PHP-FPM production    #
################################
FROM php:8.4-fpm AS php-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libicu-dev libzip-dev \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer-builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=composer-builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=composer-builder /usr/local/bin/docker-php-ext-* /usr/local/bin/
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www
COPY --from=composer-builder /var/www /var/www
COPY --from=node-build /var/www/public/build /var/www/public/build
COPY --from=node-build /var/www/public/flux /var/www/public/flux

RUN chown -R www-data:www-data /var/www
USER www-data

################################
# Stage: Nginx web             #
################################
FROM nginx:alpine AS web

COPY ./docker/common/nginx/nginx.conf /etc/nginx/nginx.conf
WORKDIR /var/www/public

# Copy site assets and public files from the node build
COPY --from=node-build /var/www/public /var/www/public

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
