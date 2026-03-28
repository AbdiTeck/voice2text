FROM php:8.2-apache

# Kopier backend til webroot
COPY backend/ /var/www/html/

# Aktiver mod_rewrite (valgfritt)
RUN a2enmod rewrite

# Tillat uploads mappe
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads