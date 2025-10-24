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
        cd /app

        echo "🔍 Verificando .env..."
        if [ ! -f ".env" ]; then
            echo "❌ Arquivo .env não encontrado!"
            ls -la /app/ | grep env
            exit 1
        fi

        echo "🔍 Verificando APP_KEY..."
        grep "APP_KEY=" .env || echo "⚠️  APP_KEY não encontrada!"

        echo "🔍 Verificando diretórios storage..."
        ls -la /app/storage/framework/

        echo "🔄 Otimizando autoloader..."
        composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts --quiet

        echo "🔄 Limpando e atualizando caches para produção..."
        php artisan config:clear --quiet || echo "⚠️  Falha ao limpar config cache"
        php artisan route:clear --quiet || echo "⚠️  Falha ao limpar route cache"
        php artisan view:clear --quiet || echo "⚠️  Falha ao limpar view cache"
        php artisan cache:clear --quiet || echo "⚠️  Falha ao limpar cache"
        php artisan config:cache --quiet || echo "⚠️  Falha ao criar config cache"
        php artisan route:cache --quiet || echo "⚠️  Falha ao criar route cache"
        php artisan view:cache --quiet || echo "⚠️  Falha ao criar view cache"
        echo "✅ Caches processados"
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
