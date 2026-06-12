# syntax=docker/dockerfile:1

# ===========================================================================
# base — shared foundation for every environment (PHP 8.4 + FrankenPHP).
# ===========================================================================
FROM dunglas/frankenphp:1-php8.4 AS base

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

# Composer binary from the official image.
COPY --from=composer/composer:2-bin /composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# ===========================================================================
# prod — APP_ENV=prod build (production AND the test/staging stage).
# No dev dependencies, assets compiled, opcache tuned for production.
# ===========================================================================
FROM base AS prod

ENV APP_ENV=prod

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install PHP dependencies first to leverage Docker layer caching.
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress \
        --no-interaction

# Copy the application source and finalize the build.
COPY . .
RUN composer dump-autoload --classmap-authoritative --no-dev \
    && composer run-script post-install-cmd --no-dev \
    && php bin/console asset-map:compile \
    && chown -R www-data:www-data var

ENTRYPOINT ["docker-php-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# ===========================================================================
# dev — APP_ENV=dev build (the develop branch stage).
# Includes dev dependencies (web profiler, maker) and the dev-friendly php.ini.
# ===========================================================================
FROM base AS dev

ENV APP_ENV=dev

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Install ALL dependencies (including require-dev) so dev bundles are present.
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress \
        --no-interaction

COPY . .
RUN composer dump-autoload \
    && composer run-script post-install-cmd \
    && php bin/console asset-map:compile \
    && chown -R www-data:www-data var

ENTRYPOINT ["docker-php-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
