ARG PHP_VERSION
FROM php:${PHP_VERSION}-fpm

RUN pecl install redis-5.3.2 && \
    pecl install apcu-5.1.19 && \
    docker-php-ext-enable redis apcu

COPY www.conf /usr/local/etc/php-fpm.d/
COPY docker-php-ext-apcu-cli.ini /usr/local/etc/php/conf.d/
