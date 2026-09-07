# syntax=docker/dockerfile:1

##############################################
# Stage 1 - PHP dependencies (composer)
##############################################
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-progress \
    --optimize-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative


##############################################
# Stage 2 - Frontend assets (vite/tailwind)
##############################################
FROM node:20-alpine AS frontend

WORKDIR /app

# Vite bakes VITE_* variables into the compiled JS at build time (not runtime),
# so they must be passed explicitly as build args instead of relying on a
# mounted .env file (which is intentionally excluded via .dockerignore).
ARG VITE_APP_NAME
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ENV VITE_APP_NAME=${VITE_APP_NAME} \
    VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN npm run build


##############################################
# Stage 3 - Runtime image (php-fpm + nginx + supervisor)
##############################################
FROM php:8.3-fpm-alpine AS app

ARG WWWUSER=1000
ARG WWWGROUP=1000

WORKDIR /var/www/html

# System packages & PHP extensions required by the app
# (pdo_mysql: database, gd/zip: maatwebsite/excel & simple-qrcode,
#  bcmath/intl: general Laravel needs, pcntl/sockets: reverb/queue workers)
RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        curl \
        git \
        unzip \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        sockets \
        gd \
        zip \
        intl \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# App source
COPY . .

# Vendor (production, no-dev) & compiled frontend assets from previous stages
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Container configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
