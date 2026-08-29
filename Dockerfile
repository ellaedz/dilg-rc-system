# syntax=docker/dockerfile:1.7

ARG PHP_IMAGE=php:8.2.12-apache-bookworm@sha256:8983699f10a9055f893b2c2f7f2c7c5a5da833b44967b25fe92bdf42b5821a72
ARG COMPOSER_IMAGE=composer:2.10.1@sha256:7725eb4545c438629ae8bde3ef0bb9a5038ef566126ad878442a69007242d267
ARG NODE_IMAGE=node:22.21.0-bookworm-slim@sha256:f9f7f95dcf1f007b007c4dcd44ea8f7773f931b71dc79d57c216e731c87a090b

FROM ${PHP_IMAGE} AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libxml2-dev \
        libpng-dev \
        libpq-dev \
        libwebp-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" exif gd opcache pdo_pgsql simplexml zip \
    && rm -rf /var/lib/apt/lists/*

FROM ${COMPOSER_IMAGE} AS composer-bin

FROM php-base AS composer-build
WORKDIR /app
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock artisan ./
COPY app app
COPY bootstrap bootstrap
COPY config config
COPY database database
COPY public public
COPY resources resources
COPY routes routes
COPY storage storage
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

FROM ${NODE_IMAGE} AS frontend-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts --no-audit --no-fund
COPY resources resources
COPY public public
COPY vite.config.js ./
RUN npm run build

FROM php-base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_SSLROOTCERT=/usr/local/share/ca-certificates/supabase-root-2021.crt \
    PORT=8080 \
    APACHE_PORT=8080

WORKDIR /var/www/html

RUN a2enmod headers rewrite \
    && rm -f /etc/apache2/sites-enabled/000-default.conf

COPY docker/laravel/ports.conf /etc/apache2/ports.conf
COPY docker/laravel/civiclear.conf /etc/apache2/sites-available/civiclear.conf
COPY docker/laravel/php-runtime.ini /usr/local/etc/php/conf.d/99-civiclear-runtime.ini
COPY docker/laravel/entrypoint.sh /usr/local/bin/civiclear-entrypoint
COPY docker/laravel/certs/supabase-root-2021.crt /usr/local/share/ca-certificates/supabase-root-2021.crt
RUN a2ensite civiclear \
    && chmod 0555 /usr/local/bin/civiclear-entrypoint \
    && chmod 0444 /usr/local/share/ca-certificates/supabase-root-2021.crt

COPY --chown=www-data:www-data artisan composer.json composer.lock ./
COPY --chown=www-data:www-data app app
COPY --chown=www-data:www-data bootstrap bootstrap
COPY --chown=www-data:www-data config config
COPY --chown=www-data:www-data database database
COPY --chown=www-data:www-data public public
COPY --chown=www-data:www-data resources resources
COPY --chown=www-data:www-data routes routes
COPY --chown=www-data:www-data storage storage
COPY --from=composer-build --chown=www-data:www-data /app/vendor vendor
COPY --from=frontend-build --chown=www-data:www-data /app/public/build public/build

RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        /tmp/civiclear \
        /var/lock/apache2 \
        /var/run/apache2 \
    && chown -R www-data:www-data \
        bootstrap/cache \
        storage \
        /tmp/civiclear \
        /var/lock/apache2 \
        /var/run/apache2 \
        /var/log/apache2 \
        /etc/apache2

USER www-data
EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/civiclear-entrypoint"]
CMD ["apache2-foreground"]
