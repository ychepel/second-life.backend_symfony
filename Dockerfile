FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk update && apk add --no-cache \
    bash \
    unzip \
    git \
    curl \
    libzip-dev \
    icu-dev \
    curl-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    imagemagick-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && docker-php-ext-install zip intl opcache pdo pdo_mysql curl

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
