FROM php:8.2-cli

# Install system dependencies
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libonig-dev libicu-dev libpng-dev \
 && docker-php-ext-install pdo pdo_mysql intl \
 && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Expose port for artisan serve
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]



