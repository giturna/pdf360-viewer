FROM php:8.2-apache

RUN apt-get update && apt-get install -y libonig-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql mbstring

COPY php-uploads.ini /usr/local/etc/php/conf.d/99-uploads.ini

RUN a2enmod rewrite
WORKDIR /var/www/html
