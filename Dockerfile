# syntax=docker/dockerfile:1

# Versions are pinned to match the project (PHP 8.4, Symfony 7.4).
FROM dunglas/frankenphp:1-php8.4 AS base

ENV APP_ENV=prod

WORKDIR /app

# System packages and PHP extensions required by the app.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && install-php-extensions \
        intl \
        pdo_pgsql \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Production-friendly PHP defaults.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Composer binary from the official image.
COPY --from=composer/composer:2-bin /composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# ---------------------------------------------------------------------------
# Install PHP dependencies first to leverage Docker layer caching.
# ---------------------------------------------------------------------------
COPY composer.json composer.lock symfony.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress \
        --no-interaction

# ---------------------------------------------------------------------------
# Copy the application source and finalize the build.
# ---------------------------------------------------------------------------
COPY . .

RUN composer dump-autoload --classmap-authoritative --no-dev \
    && composer run-script post-install-cmd --no-dev \
    && php bin/console asset-map:compile

# Writable directories for the runtime user.
RUN chown -R www-data:www-data var

# FrankenPHP serves the app from public/ and listens on :80 (and :443).
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
