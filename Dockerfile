FROM php:8.1-apache

# системні пакети + mysql client
RUN apt-get update && apt-get install -y \
    unzip git curl nano sqlite3 libsqlite3-dev libzip-dev zip libxml2-dev \
    default-mysql-client \
 && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip \
 && echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini

# віртуальний хост на backend/web
RUN a2enmod rewrite
COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY . /var/www/html

# entrypoint
COPY docker/app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
