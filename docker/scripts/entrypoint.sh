#!/bin/sh

echo "🚀 Iniciando container Initfly..."

# Garantir que /tmp tem permissões corretas para uploads do PHP
chmod 1777 /tmp 2>/dev/null || echo "⚠️ Não foi possível ajustar permissões do /tmp"

# Executar inicialização do storage apenas para o container app
# Aceita "app" ou qualquer role começando com "app_"
ROLE="${CONTAINER_ROLE:-app}"
case "$ROLE" in
    app|app_*)
        echo "📦 Container role: $ROLE - executando inicialização do storage"

    # Aguardar um pouco para garantir que volumes estão montados
    sleep 2

    # Executar script de inicialização do storage
    /app/docker/scripts/init-storage.sh

    if [ $? -ne 0 ]; then
        echo "❌ Falha na inicialização do storage"
        exit 1
    fi

    echo "✅ Storage inicializado com sucesso"

    # Executar cache refresh se necessário
    if [ "${APP_ENV}" = "production" ]; then
        echo "🔄 Limpando e atualizando caches para produção..."
        cd /app
        php artisan config:clear --quiet
        php artisan route:clear --quiet
        php artisan view:clear --quiet
        php artisan cache:clear --quiet
        php artisan config:cache --quiet
        php artisan route:cache --quiet
        php artisan view:cache --quiet
        echo "✅ Caches limpos e atualizados"
    fi
    ;;

queue|queue_*)
    ROLE="${CONTAINER_ROLE:-queue}"
    echo "📦 Container role: $ROLE - aguardando Redis..."

    # Aguardar Redis estar disponível
    while ! php artisan queue:work --stop-when-empty --tries=1 --timeout=1 --quiet >/dev/null 2>&1; do
        echo "⏳ Aguardando Redis ficar disponível..."
        sleep 2
    done

    echo "✅ Redis disponível"
    ;;

scheduler|scheduler_*)
    ROLE="${CONTAINER_ROLE:-scheduler}"
    echo "📦 Container role: $ROLE - inicialização do agendador"
    ;;

init|init_*)
    echo "📦 Container role: ${CONTAINER_ROLE:-init} - executando apenas inicialização"
    exec "$@"
    exit 0
    ;;

*)
    echo "⚠️  Container role desconhecido: ${CONTAINER_ROLE:-none}"
    echo "Prosseguindo sem inicialização especial..."
    ;;
esac

php artisan storage:link

echo "🎯 Iniciando aplicação com: $@"

# Garantir que estamos no diretório correto
cd /app

# Desabilitar prompts interativos do Laravel
export LARAVEL_SAIL=1

# Garantir que o binário FrankenPHP tem permissão de execução
if [ -f /usr/local/bin/frankenphp ]; then
    chmod +x /usr/local/bin/frankenphp
    echo "✅ Permissões do FrankenPHP configuradas"
fi

# Executar comando passado como argumento com stdin fechado para evitar prompts
exec "$@" < /dev/null
