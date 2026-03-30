# syntax=docker/dockerfile:1.7

FROM composer:2 AS composer_bin

FROM php:8.3-cli-alpine AS composer_vendor
WORKDIR /app

COPY --from=composer_bin /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
        git \
        icu-data-full \
        icu-libs \
        libzip \
        oniguruma \
        unzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        mbstring \
        zip \
    && apk del .build-deps

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --classmap-authoritative \
    --no-scripts


FROM node:22-alpine AS frontend_assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=composer_vendor /app/vendor ./vendor

RUN npm run build


FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        curl \
        fcgi \
        icu-data-full \
        icu-libs \
        libpng \
        libjpeg-turbo \
        freetype \
        libwebp \
        libzip \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && apk del .build-deps

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-nebuliton.ini
COPY docker/php/entrypoint.sh /usr/local/bin/nebuliton-entrypoint
RUN chmod +x /usr/local/bin/nebuliton-entrypoint

COPY . .
COPY --from=composer_vendor /app/vendor ./vendor
COPY --from=frontend_assets /app/public/build ./public/build

RUN rm -rf node_modules \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

EXPOSE 9000

ENTRYPOINT ["nebuliton-entrypoint"]
CMD ["php-fpm", "-F"]


FROM nginx:1.27-alpine AS web
WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/40-storage-link.sh /docker-entrypoint.d/40-storage-link.sh
COPY --from=frontend_assets /app/public ./public

RUN chmod +x /docker-entrypoint.d/40-storage-link.sh \
    && mkdir -p /var/www/html/storage/app/public

EXPOSE 80
