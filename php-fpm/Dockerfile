FROM php:7.3-fpm

RUN pecl install redis-5.0.2 && \
    pecl install apcu-5.1.17 && \
    docker-php-ext-enable redis apcu

COPY www.conf /usr/local/etc/php-fpm.d/
COPY docker-php-ext-apcu-cli.ini /usr/local/etc/php/conf.d/
