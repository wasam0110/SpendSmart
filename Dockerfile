FROM php:8.2-apache

# Enable Apache modules commonly needed by PHP apps
RUN a2enmod rewrite

# Copy app source
COPY . /var/www/html/

# Ensure runtime-writable data directory for JSON storage
RUN chown -R www-data:www-data /var/www/html/data && chmod -R 775 /var/www/html/data

EXPOSE 80
