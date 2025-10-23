FROM php:8.3-fpm-alpine AS base

# Install required PHP extensions
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    icu-dev \
    postgresql-dev \
    libxml2-dev \
    libzip-dev \
    mysql-dev \
    && docker-php-ext-install \
    intl \
    bcmath \
    pgsql \
    pdo_pgsql \
    pdo_mysql \
    mysqli \
    dom \
    zip \
    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache \
    && apk del $PHPIZE_DEPS

# Install Nginx and other dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    su-exec \
    wget \
    curl \
    $PHPIZE_DEPS \
    icu-dev \
    postgresql-dev \
    libxml2-dev \
    libzip-dev \
    mysql-dev \
    nodejs \
    npm \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    busybox-extras \
    ffmpeg \
    && docker-php-ext-install \
    intl \
    bcmath \
    pgsql \
    pdo_pgsql \
    pdo_mysql \
    mysqli \
    dom \
    zip \
    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install opcache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && apk del $PHPIZE_DEPS

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /run/nginx \
    /run/supervisor \
    /run/php-fpm \
    /var/log/supervisor \
    /var/log/php-fpm && \
    touch /var/log/php-fpm/error.log && \
    touch /var/log/php-fpm/slow.log && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage && \
    chown -R www-data:www-data /var/log/php-fpm && \
    chmod -R 755 /var/log/php-fpm && \
    chown -R www-data:www-data /run/php-fpm && \
    chmod -R 755 /run/php-fpm

# Configure PHP
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application files
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# Generate Ziggy routes
RUN php artisan ziggy:generate resources/js/ziggy.js

# Install and build frontend assets
RUN npm install && npm run build

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh

# Remove or override zz-docker.conf
RUN rm -f /usr/local/etc/php-fpm.d/zz-docker.conf

# Make start script executable
RUN chmod +x /usr/local/bin/start.sh

# Expose Nginx port
EXPOSE 9080

# Health check with increased timeout and start period
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://127.0.0.1:9080/health || exit 1

# Start services using supervisor
CMD ["/usr/local/bin/start.sh"]
