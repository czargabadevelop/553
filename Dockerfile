# syntax=docker/dockerfile:1

########## Frontend assets ##########
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

########## Composer dependencies ##########
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .

# Bootstrap caches generated on a developer machine can reference dev-only
# packages (e.g. laravel/boost) that are not installed with --no-dev.
RUN rm -f bootstrap/cache/*.php \
    && composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

########## Runtime (PHP-FPM) ##########
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) bcmath intl opcache pdo_mysql pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

########## Runtime (Nginx web server) ##########
# Serves static assets and proxies PHP requests to the `app` service above.
# In production this can be skipped and aaPanel's host Nginx can proxy straight
# to the `app` container, but it makes `docker compose up` usable on its own.
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=assets /app/public /var/www/html/public

EXPOSE 80
