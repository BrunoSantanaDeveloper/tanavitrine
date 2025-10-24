# Stage 1: Frontend Assets build
FROM oven/bun:1-slim AS node-builder
WORKDIR /app
COPY . .
RUN bun install --no-frozen-lockfile || (rm -f bun.lockb && bun install)
RUN bun run build

# Stage 2: Final image
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    wget \
    mysql-client \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    zip \
    bcmath \
    intl \
    gd \
    exif \
    pcntl && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del $PHPIZE_DEPS && \
    rm -rf /tmp/* /var/cache/apk/*

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create required directories
RUN mkdir -p \
    /var/www/html \
    /run/nginx \
    /run/php-fpm \
    /var/log/supervisor && \
    addgroup -g 1000 appgroup && \
    adduser -u 1000 -G appgroup -h /var/www/html -s /bin/sh -D appuser

# Environment configuration
ENV APP_ENV=production \
    APP_DEBUG=false

# Configure PHP for production
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo "upload_max_filesize = 100M" >> "$PHP_INI_DIR/conf.d/uploads.ini" && \
    echo "post_max_size = 100M" >> "$PHP_INI_DIR/conf.d/uploads.ini" && \
    echo "memory_limit = 256M" >> "$PHP_INI_DIR/conf.d/uploads.ini" && \
    echo "max_execution_time = 300" >> "$PHP_INI_DIR/conf.d/uploads.ini"

# Set up application
WORKDIR /var/www/html
COPY --chown=appuser:appgroup . .
COPY --from=node-builder --chown=appuser:appgroup /app/public/build/ ./public/build/

# Create Laravel required directories
RUN mkdir -p \
    storage/framework/{sessions,views,cache,testing} \
    storage/logs \
    storage/app/public \
    bootstrap/cache && \
    chown -R appuser:appgroup storage bootstrap/cache && \
    chmod -R 755 storage bootstrap/cache

# Install dependencies
RUN composer install --no-dev --prefer-dist --no-scripts && \
    composer dump-autoload --no-scripts && \
    chown -R appuser:appgroup /var/www/html && \
    chmod -R 755 storage bootstrap/cache && \
    rm -rf tests node_modules && \
    composer clear-cache

# Copy Nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy PHP-FPM configuration
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
RUN rm -f /usr/local/etc/php-fpm.d/zz-docker.conf

# Copy supervisord configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy start script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose port
EXPOSE 8001

# Start supervisor
CMD ["/usr/local/bin/start.sh"]
