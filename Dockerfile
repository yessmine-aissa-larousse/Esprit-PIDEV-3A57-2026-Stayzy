FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        opcache \
        mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application (vendor installed via volume or build)
COPY . /var/www/html

# Fix permissions
RUN chown -R www-data:www-data /var/www/html/var 2>/dev/null || true

EXPOSE 9000

CMD ["php-fpm"]
