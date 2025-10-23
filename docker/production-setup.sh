#!/bin/bash
set -e

# Função para log
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Verificar se estamos em produção
if [ "$APP_ENV" != "production" ]; then
    log "Error: This script should only be run in production environment"
    exit 1
fi

# Verificar se o banco de dados está acessível
log "Checking database connection..."
if ! mysql -h db -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -e "SELECT 1" >/dev/null 2>&1; then
    log "Error: Could not connect to database"
    exit 1
fi

# Gerar APP_KEY se não existir
if [ -z "$APP_KEY" ]; then
    log "Generating new APP_KEY..."
    export APP_KEY=$(php artisan key:generate --show)
    log "New APP_KEY generated: $APP_KEY"
fi

# Backup do banco antes de qualquer operação
log "Creating database backup..."
BACKUP_DIR="/backup/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
mysqldump -h db -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" > "$BACKUP_DIR/backup.sql"
log "Backup created at $BACKUP_DIR/backup.sql"

# Executar migrações
log "Running migrations..."
php artisan migrate --force

# Perguntar se deseja executar seeds
read -p "Do you want to run database seeds? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    log "Running database seeds..."
    php artisan db:seed --force
fi

# Limpar cache
log "Clearing and rebuilding cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

log "Production setup completed successfully!"
