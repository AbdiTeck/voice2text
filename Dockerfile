FROM php:8.2-apache

# Kopier alt
COPY . /var/www/html/

# Flytt frontend til root
RUN mv /var/www/html/frontend/* /var/www/html/

# Lag uploads mappe
RUN mkdir -p /var/www/html/backend/uploads && chmod -R 777 /var/www/html/backend/uploads

# Aktiver Apache
RUN a2enmod rewrite
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads