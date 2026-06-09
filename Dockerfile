FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev zip unzip git curl \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && php artisan optimize \
    && php artisan storage:link 2>/dev/null || true

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000
