# syntax=docker/dockerfile:1

###############################################################################
# Stage 1 — build the frontend assets (Webpack Encore → public/build/)
###############################################################################
FROM node:22-alpine AS assets
WORKDIR /app
# @symfony/ux-turbo is a "file:vendor/symfony/ux-turbo/assets" dependency, so it
# only exists after `composer install` (Stage 2). The build context on the server
# has no vendor/ (composer runs inside the image), so pull the assets from the
# composer_builder stage BEFORE npm ci — otherwise npm run build fails with
# "The file @symfony/ux-turbo/package.json could not be found".
COPY --from=composer_builder /app/vendor/symfony/ux-turbo/assets /app/vendor/symfony/ux-turbo/assets
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

###############################################################################
# Stage 2 — install PHP dependencies (no dev, optimized autoloader)
###############################################################################
FROM dunglas/frankenphp:1-php8.5-alpine AS composer_builder
WORKDIR /app

# Install Composer via the official installer (Alpine ships no composer binary).
RUN apk add --no-cache curl && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress
COPY . .
RUN mkdir -p var && composer dump-autoload --optimize --no-dev

###############################################################################
# Stage 3 — runtime image
###############################################################################
FROM dunglas/frankenphp:1-php8.5-alpine AS app
WORKDIR /app

# PHP extensions required by the app (mirror of .devcontainer/Dockerfile + php-config/php.ini).
# install-php-extensions ships with the FrankenPHP image and resolves system deps.
RUN install-php-extensions pdo_sqlite sqlite3 intl gd zip bcmath opcache

# Application code + optimized vendor.
COPY --from=composer_builder /app /app
# Frontend build output overlaid last (authoritative).
COPY --from=assets /app/public/build /app/public/build

# Caddy configuration (auto-TLS, genealia.fr server block).
COPY Caddyfile /etc/frankenphp/Caddyfile

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    FRANKENPHP_WORKER_MAX_REQUESTS=100

# var/ holds the SQLite DB + uploaded client documents — persisted via a named volume.
RUN mkdir -p var/uploads var/cache var/log

VOLUME /app/var

EXPOSE 80 443 443/udp