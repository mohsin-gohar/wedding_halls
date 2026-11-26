# syntax=docker/dockerfile:1

################################################################
# Stage 1 — build production frontend assets
################################################################
FROM node:20-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

################################################################
# Stage 2 — production PHP runtime
################################################################
FROM php:8.2-fpm AS app

# $PHPIZE_DEPS is provided by the official PHP image and is removed again after
# compiling the Redis extension so it does not bloat the runtime image.
RUN apt-get update && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        git \
        curl \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

# Align PHP upload/runtime limits with the validated media upload contract.
COPY deploy/php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

# Install Composer (pinned major so builds stay reproducible)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy the application, then overlay the assets built in stage 1.
COPY . .
COPY --from=assets /app/public/build ./public/build

# Install PHP dependencies from the lock file (no dev packages in production).
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Create writable runtime directories. The entrypoint re-applies permissions
# when a persistent storage volume is mounted over /var/www/storage.
RUN mkdir -p /var/www/storage/framework/{sessions,views,cache} \
    && mkdir -p /var/www/storage/logs \
    && chown -R www-data:www-data /var/www/storage \
    && chown -R www-data:www-data /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Supervisor is the container entrypoint so PHP-FPM, the queue worker and the
# scheduler all run; starting php-fpm directly silently skipped the latter two.
COPY deploy/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisor.conf"]

################################################################
# Stage 3 — static web edge (shares only public uploads volume)
################################################################
FROM nginx:alpine AS web

COPY public/ /var/www/public/
COPY --from=assets /app/public/build /var/www/public/build
COPY deploy/nginx.conf /etc/nginx/conf.d/default.conf
