FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable rewrite
RUN a2enmod rewrite

# COMPLETELY remove ALL MPM modules and reinstall only prefork
RUN apt-get update && \
    apt-get purge -y apache2-bin && \
    apt-get install -y apache2-bin && \
    a2enmod mpm_prefork

# Copy files
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
