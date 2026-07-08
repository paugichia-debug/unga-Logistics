FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

# Use PHP's built-in server (no Apache MPM issues)
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
