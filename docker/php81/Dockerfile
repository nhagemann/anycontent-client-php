FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y git \
    && apt-get install -y --no-install-recommends  \
    zip  \
    libfreetype6-dev  \
    libjpeg62-turbo-dev \
    libicu-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql gd intl

RUN docker-php-ext-configure gd

RUN apt-get install -y zip unzip

RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Endless command to keep container running
CMD tail -f /dev/null