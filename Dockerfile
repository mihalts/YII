FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    unzip git sqlite3 libsqlite3-dev libzip-dev zip libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip

RUN a2enmod rewrite

COPY . /var/www/html
WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html
