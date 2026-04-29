FROM php:8.3-apache

WORKDIR /var/www/html

COPY public/ /var/www/html/

RUN a2enmod rewrite

EXPOSE 80
