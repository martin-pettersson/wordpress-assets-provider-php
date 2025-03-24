FROM composer:2 AS composer
FROM php:8.3-cli AS php

RUN apt update && \
    apt install -y libzip-dev zip && \
    pecl install xdebug && \
    docker-php-ext-configure zip && \
    docker-php-ext-install zip && \
    docker-php-ext-enable xdebug

ENV XDEBUG_MODE=coverage

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
