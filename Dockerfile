FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk update && apk add --no-cache \
    bash \
    unzip \
    git \
    curl \
    libzip-dev \
    libicu-dev \
    pdo_mysql \
    icu-dev \
    && docker-php-ext-install zip \
    intl \
    opcache \
    && apk add --no-cache curl-dev \
    && docker-php-ext-install curl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Set the working directory
WORKDIR /var/www/html

# Expose the default PHP-FPM port
EXPOSE 9000
