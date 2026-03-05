FROM php:8.3-fpm

# Встановлення розширень для роботи з MySQL.
RUN docker-php-ext-install pdo_mysql

WORKDIR /var/www/html
