# Stage 1: Build stage
FROM php:8.2-fpm-alpine as build

# Install dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libxml2-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    $PHPIZE_DEPS

# Configure and Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
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
    libwebp \
    libjpeg-turbo \
    freetype \
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

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
