# Stage 1: Build stage
FROM php:8.2-fpm-alpine as build

# Install dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libxml2-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy existing application directory contents
COPY . .

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Stage 2: Production stage
FROM php:8.2-fpm-alpine

# Install runtime dependencies
RUN apk add --no-cache \
    libpng \
    libzip \
    icu-libs \
    oniguruma \
    mariadb-client

# Copy PHP extensions from build stage
COPY --from=build /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=build /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

WORKDIR /var/www

# Copy application from build stage
COPY --from=build /var/www /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Performance optimization: PHP-FPM settings (optional but good)
# RUN sed -i 's/pm.max_children = 5/pm.max_children = 20/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000
CMD ["php-fpm"]
