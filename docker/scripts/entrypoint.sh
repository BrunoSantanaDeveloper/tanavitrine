#!/bin/sh

echo "🚀 Iniciando container Initfly..."

# Executar inicialização do storage apenas para o container app
if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    echo "📦 Container role: app - executando inicialização do storage"

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

elif [ "${CONTAINER_ROLE}" = "queue" ]; then
    echo "📦 Container role: queue - aguardando Redis..."

    # Aguardar Redis estar disponível
    while ! php artisan queue:work --stop-when-empty --tries=1 --timeout=1 --quiet >/dev/null 2>&1; do
        echo "⏳ Aguardando Redis ficar disponível..."
        sleep 2
    done

    echo "✅ Redis disponível"

elif [ "${CONTAINER_ROLE}" = "init" ]; then
    echo "📦 Container role: init - executando apenas inicialização"
    exec "$@"
    exit 0
fi

php artisan storage:link

echo "🎯 Iniciando aplicação com: $@"

# Garantir que estamos no diretório correto
cd /app

# Executar comando passado como argumento
exec "$@"
