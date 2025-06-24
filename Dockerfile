FROM php:8.2-apache

RUN apt-get update && apt-get install -y libonig-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql mbstring

#RUN mkdir -p /var/www/html/uploads/{pdfs,360images,tmp} \
# && chown -R www-data:www-data /var/www/html/uploads

RUN mkdir -p /var/www/html/uploads/pdfs \
            /var/www/html/uploads/360images \
            /var/www/html/uploads/tmp

COPY php-uploads.ini /usr/local/etc/php/conf.d/99-uploads.ini

RUN a2enmod rewrite
WORKDIR /var/www/html
