# Stage 1: Frontend Assets build
FROM oven/bun:1-slim AS node-builder
WORKDIR /app
COPY . .
RUN bun install --no-frozen-lockfile || (rm -f bun.lockb && bun install)
RUN bun run build

# Stage 2: Final image
FROM dunglas/frankenphp:1.4.0-php8.3-alpine AS base

# Create required directories with proper permissions
RUN mkdir -p /data/caddy /config/caddy /home/.local/share/caddy && \
    chmod -R 755 /data /config /home/.local && \
    # Add non-root user
    addgroup -g 1000 appgroup && \
    adduser -u 1000 -G appgroup -h /app -s /bin/sh -D appuser && \
    # Give ownership of Caddy directories
    chown -R appuser:appgroup /data /config /home/.local

# Set Caddy environment variables
ENV XDG_CONFIG_HOME=/config \
    XDG_DATA_HOME=/data

# Install composer and PHP extensions
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN install-php-extensions \
    pcntl \
    intl \
    pdo_mysql \
    zip \
    bcmath \
    redis \
    gd \
    exif && \
    # Cleanup
    rm -rf /tmp/* /var/cache/apk/*

# Environment configuration
ENV APP_ENV=production \
    APP_DEBUG=false \
    OCTANE_SERVER=frankenphp

# Configure PHP for production
COPY docker/php/production.ini $PHP_INI_DIR/conf.d/
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set up application
WORKDIR /app
COPY --chown=appuser:appgroup . .
COPY --from=node-builder --chown=appuser:appgroup /app/public/build/ ./public/build/

# Create Laravel required directories before composer install
RUN mkdir -p storage/framework/{sessions,views,cache,testing} \
    storage/logs \
    bootstrap/cache && \
    chown -R appuser:appgroup storage bootstrap/cache && \
    chmod -R 755 storage bootstrap/cache

# Install dependencies (optimization will happen at runtime)
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader && \
    # Set proper permissions
    chown -R appuser:appgroup /app && \
    chmod -R 755 storage bootstrap/cache && \
    rm -rf tests node_modules && \
    composer clear-cache

# Copy and make entrypoint scripts executable (after cleanup)
COPY --chown=appuser:appgroup docker/scripts/ ./docker/scripts/
RUN chmod +x ./docker/scripts/*.sh

# Copy custom Caddyfile
COPY --chown=appuser:appgroup docker/caddy/Caddyfile /etc/caddy/Caddyfile

USER appuser

# Expose port
EXPOSE 8000

ENTRYPOINT ["/app/docker/scripts/entrypoint.sh"]
CMD ["php", "artisan", "octane:start", "--host=0.0.0.0"]
