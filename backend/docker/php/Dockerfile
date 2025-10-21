FROM php:8.2-fpm-alpine

# system deps
RUN apk add --no-cache curl git unzip tzdata

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP ini
COPY docker/php/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

# Install PHP deps if composer files exist (fine on first build)
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist || true

# Copy source (also bind-mounted in dev)
COPY . /app

# writable data directory (no logs/ dir)
RUN mkdir -p /app/data && chown -R www-data:www-data /app/data

EXPOSE 9000
CMD ["php-fpm"]
