FROM php:8.2-apache

# Installer mysqli (VIKTIG!)
RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

COPY backend/ ./backend/

# Lag uploads mappe
RUN mkdir -p backend/uploads \
    && chmod -R 755 backend/uploads

EXPOSE 80