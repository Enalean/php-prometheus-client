FROM php:7.2-fpm

RUN pecl install redis-4.3.0 && \
    pecl install apcu-5.1.17 && \
    docker-php-ext-enable redis apcu

COPY www.conf /usr/local/etc/php-fpm.d/
COPY docker-php-ext-apcu-cli.ini /usr/local/etc/php/conf.d/
