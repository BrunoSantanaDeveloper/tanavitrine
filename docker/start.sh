#!/bin/sh
set -e  # Parar em caso de erro

# Enable debug mode
set -x

# Criar diretórios necessários para a aplicação
echo "Creating necessary directories..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/log/supervisor
mkdir -p /run/nginx
mkdir -p /run/supervisor
mkdir -p /run/php-fpm
mkdir -p /var/lib/nginx/logs
mkdir -p /var/lib/nginx/tmp/client_body
mkdir -p /var/lib/nginx/tmp/proxy
mkdir -p /var/lib/nginx/tmp/fastcgi
mkdir -p /var/lib/nginx/tmp/uwsgi
mkdir -p /var/lib/nginx/tmp/scgi

# Verificar e configurar diretório public
echo "Setting up public directory..."
mkdir -p /var/www/html/public
chown -R www-data:www-data /var/www/html/public
chmod -R 755 /var/www/html/public

# Verificar se index.php existe e configurar permissões
if [ -f /var/www/html/public/index.php ]; then
    echo "index.php found, setting permissions..."
    chown www-data:www-data /var/www/html/public/index.php
    chmod 644 /var/www/html/public/index.php
else
    echo "WARNING: index.php not found in /var/www/html/public/"
    ls -la /var/www/html/public/
fi

# Remover socket antigo se existir
echo "Cleaning up old socket..."
rm -f /run/php-fpm-tanavitrine.sock
pkill -f php-fpm || true

# Configurar permissões do diretório do socket PHP-FPM
echo "Setting socket permissions..."
chown -R www-data:www-data /run
chmod -R 775 /run

# Configurar permissões dos diretórios Nginx
echo "Setting Nginx directory permissions..."
chown -R www-data:www-data /var/lib/nginx
chmod -R 755 /var/lib/nginx
chown -R www-data:www-data /var/log/nginx
chmod -R 755 /var/log/nginx

# Configurar permissões dos diretórios Laravel
echo "Setting Laravel directory permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/log/supervisor
chmod -R 755 /run/supervisor

# Criar/recriar o symlink de storage (deve ser feito em runtime após volumes montados)
echo "Creating storage symlink..."
rm -f /var/www/html/public/storage
php artisan storage:link

# Verificar conexão com o banco de dados
echo "Testing database connection..."
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"

# Testar conexão com o banco usando telnet
echo "Testing if database port is open..."
if ! command -v telnet &> /dev/null; then
    echo "Telnet is not available, installing..."
    apk add --no-cache busybox-extras
fi

telnet $DB_HOST $DB_PORT | grep -q "Connected"
if [ $? -eq 0 ]; then
    echo "Database port is open!"
    echo "Waiting for database connection..."

    max_attempts=30
    attempt=0

    while [ $attempt -lt $max_attempts ]; do
        echo "Attempting to connect to database ($DB_HOST:$DB_PORT)..."
        php artisan db:monitor

        if [ $? -eq 0 ]; then
            echo "Database connection established!"
            break
        fi

        attempt=$((attempt + 1))
        sleep 2
    done

    if [ $attempt -eq $max_attempts ]; then
        echo "Failed to connect to database after $max_attempts attempts."
        exit 1
    fi
else
    echo "Database port is not open!"
    exit 1
fi

# Otimizar aplicação
echo "Optimizing Laravel..."
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache
php artisan event:cache

# Publicar assets do Livewire e Filament
echo "Publishing Livewire and Filament assets..."
php artisan vendor:publish --force --tag=livewire:assets
php artisan filament:assets

# Garantir que os diretórios de log existem e têm as permissões corretas
mkdir -p /var/www/html/storage/logs
chmod -R 755 /var/www/html/storage/logs

# Criar diretório de logs do supervisor se não existir
mkdir -p /var/log/supervisor
chown -R www-data:www-data /var/log/supervisor
chmod 755 /var/log/supervisor

# Iniciar o supervisor
echo "Starting supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
