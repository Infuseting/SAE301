# Stage 1: Build Frontend Assets
FROM node:20 AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Build Backend Dependencies
FROM composer:2 AS backend
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --ignore-platform-reqs --no-interaction --prefer-dist --no-scripts
COPY . .
RUN composer dump-autoload --optimize 

# Stage 3: Production Image
FROM php:8.4-apache
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Set Working Directory
WORKDIR /var/www/html

# Copy Files from Stages
#COPY --from=backend /app/vendor /var/www/html/vendor
COPY --from=backend /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Set Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configure Apache Document Root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Generate Swagger Documentation
RUN cp .env.example .env 
RUN php artisan key:generate
RUN php artisan l5-swagger:generate
RUN rm .env

# Expose Port
EXPOSE 80

# Define Volume for persistent storage
VOLUME ["/var/www/html/storage/app/public"]
