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

# Copy application
COPY . /var/www/html

# Install composer dependencies as root
RUN composer install --no-interaction --optimize-autoloader

# Create var directory with proper permissions
RUN mkdir -p /var/www/html/var && chmod -R 777 /var/www/html/var && \
    chown -R www-data:www-data /var/www/html

# Create custom entrypoint script to allow FPM to run with proper settings
RUN mkdir -p /usr/local/etc/php-fpm.d && \
    echo "[www]" > /usr/local/etc/php-fpm.d/docker.conf && \
    echo "user = www-data" >> /usr/local/etc/php-fpm.d/docker.conf && \
    echo "group = www-data" >> /usr/local/etc/php-fpm.d/docker.conf && \
    echo "listen = 9000" >> /usr/local/etc/php-fpm.d/docker.conf && \
    echo "pm = static" >> /usr/local/etc/php-fpm.d/docker.conf && \
    echo "pm.max_children = 5" >> /usr/local/etc/php-fpm.d/docker.conf

EXPOSE 9000

CMD ["php-fpm"]
