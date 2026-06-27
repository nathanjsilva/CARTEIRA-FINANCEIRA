FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        nginx \
        mysql-client \
        git \
        curl \
        libzip-dev \
        zip \
        unzip \
        icu-dev \
        libpng-dev \
        jpeg-dev \
        libwebp-dev \
        freetype-dev \
        libxml2-dev \
        oniguruma-dev \
        postgresql-dev \
        sqlite-dev \
        nodejs \
        npm \
        $PHPIZE_DEPS \
        && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        zip \
        intl \
        gd \
        exif \
        pcntl \
        bcmath \
        opcache \
        && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
        && docker-php-ext-install gd \
        && docker-php-ext-enable opcache \
        && apk del $PHPIZE_DEPS

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN adduser -D -g 'www' www \
    && chown -R www:www /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

USER www

EXPOSE 9000
CMD ["php-fpm"]

