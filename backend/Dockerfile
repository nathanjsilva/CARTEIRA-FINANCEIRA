FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache nginx mysql-client git curl zip unzip \
    && install-php-extensions pdo_mysql zip intl gd exif pcntl bcmath opcache

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

EXPOSE 9000
CMD ["php-fpm"]
