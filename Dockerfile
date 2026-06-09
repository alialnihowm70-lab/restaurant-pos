FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev zip unzip git curl libicu-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite bcmath intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN cp .env.example .env 2>/dev/null || true \
    && composer install --no-dev --optimize-autoloader \
    && php artisan key:generate --force 2>/dev/null || true

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000
