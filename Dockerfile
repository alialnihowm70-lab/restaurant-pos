FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev zip unzip git curl libicu-dev libpq-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite bcmath intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && printf "APP_DEBUG=true\nAPP_KEY=base64:OEuXqJeSz0sqlLlKqZUEt+Tr5Scxqcb2DpZ9iqmLL2w=\n" > .env \
    && npm install && npm run build

EXPOSE 10000

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000
