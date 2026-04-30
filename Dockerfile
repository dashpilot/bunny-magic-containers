FROM php:8.3-apache

WORKDIR /var/www/html

COPY docker/apache-allow-htaccess.conf /etc/apache2/conf-available/allow-htaccess.conf
RUN a2enmod rewrite \
    && a2enconf allow-htaccess

COPY public/ /var/www/html/

EXPOSE 80
