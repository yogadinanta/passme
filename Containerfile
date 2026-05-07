FROM php:8.2-apache

RUN docker-php-ext-install sqlite3

COPY . /var/www/html/