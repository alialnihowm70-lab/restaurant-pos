FROM php:8.3-apache

# Install system dependencies and PHP extensions (GD, SQLite, PostgreSQL, and Intl)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    curl \
    sqlite3 \
    libsqlite3-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_sqlite pdo_pgsql intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js & npm (needed to compile frontend assets)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Enable Apache mod_rewrite module for Laravel routing
RUN a2enmod rewrite

# Configure Apache DocumentRoot to point to Laravel's public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set Working Directory
WORKDIR /var/www/html

# Copy all project source code
COPY . .

# Install PHP dependencies (production optimized)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install npm dependencies and build Vite assets
RUN npm install
RUN npm run build

# Grant write permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose HTTP port
EXPOSE 80

# Execute database setup, migrations, and start Apache web server
CMD sh -c "if [ \"\$DB_CONNECTION\" = \"sqlite\" ]; then mkdir -p /var/www/html/storage/db && touch /var/www/html/storage/db/database.sqlite && chown -R www-data:www-data /var/www/html/storage/db; fi && php artisan migrate --force && apache2-foreground"
