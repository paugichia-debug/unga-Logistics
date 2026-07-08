FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Remove conflicting MPM modules
RUN a2dismod mpm_event mpm_worker || true

# Enable only prefork
RUN a2enmod mpm_prefork

RUN a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

# Start Apache with explicit config
CMD ["apache2-foreground"]
