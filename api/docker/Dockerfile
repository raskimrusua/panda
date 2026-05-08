FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        postgresql-dev libpng-dev oniguruma-dev libzip-dev linux-headers icu-dev \
        $PHPIZE_DEPS \
 && docker-php-ext-install pdo pdo_pgsql gd mbstring zip bcmath intl \
 && pecl install redis && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
