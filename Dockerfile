# Stage 1: Build Frontend Assets
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci && npm cache clean --force
COPY resources/js resources/js
COPY resources/css resources/css
COPY vite.config.js jsconfig.json postcss.config.js tailwind.config.js ./
RUN npm run build
RUN npm prune --production && rm -rf .npm

# Stage 2: Build Backend Dependencies
FROM composer:2.7 AS backend
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts && \
    rm -rf /tmp/pear && composer clear-cache

# Stage 3: Production Image
FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl \
    && rm -rf /var/lib/apt/lists/*
    
# Enable Apache Modules
RUN a2enmod rewrite

# Set Working Directory
WORKDIR /var/www/html

# Copy Files from Stages
COPY --from=backend /app/composer.json /app/composer.lock ./
COPY --from=backend /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY --chown=www-data:www-data . .

# Set permissions
RUN mkdir -p storage/app/public bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Configure Apache Document Root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -i "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/*.conf && \
    sed -i "s|/var/www/|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Generate Swagger Documentation and clean up
RUN cp .env.example .env && \
    php artisan key:generate && \
    php artisan l5-swagger:generate && \
    rm .env

# Expose Port
EXPOSE 80

# Define Volume for persistent storage
VOLUME ["/var/www/html/storage/app/public"]
