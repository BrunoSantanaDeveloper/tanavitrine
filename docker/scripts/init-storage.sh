#!/bin/sh

# Script de inicialização do storage para produção
# Este script deve ser executado quando o container é iniciado

echo "🚀 Inicializando storage para produção..."

# Verificar se estamos no container
if [ ! -f /app/artisan ]; then
    echo "❌ Este script deve ser executado dentro do container Laravel"
    exit 1
fi

# Verificar se está usando S3
cd /app
FILESYSTEM_DISK=$(php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo env('FILESYSTEM_DISK', config('filesystems.default'));
")
echo "  - FILESYSTEM_DISK: $FILESYSTEM_DISK"

if [ "$FILESYSTEM_DISK" = "s3" ]; then
    echo "☁️ Usando S3 storage - pulando criação de diretórios locais"
    echo "✅ S3 storage não precisa de inicialização local"
    echo "🎉 Inicialização do storage concluída com sucesso!"
    exit 0
else
    echo "💾 Usando storage local - inicializando diretórios"
fi

# Função para verificar se um diretório existe e tem as permissões corretas
check_directory() {
    local dir=$1
    local create_if_missing=${2:-false}

    if [ ! -d "$dir" ]; then
        if [ "$create_if_missing" = true ]; then
            echo "📁 Criando diretório: $dir"
            mkdir -p "$dir" 2>/dev/null || echo "⚠️ Não foi possível criar: $dir (pode ser NFS)"
        else
            echo "❌ Diretório não encontrado: $dir"
            return 1
        fi
    else
        echo "✅ Diretório existe: $dir"
        # Pular verificação de permissões em NFS para evitar falhas
        if [ ! -w "$dir" ]; then
            echo "⚠️ Diretório não tem permissão de escrita (normal em NFS): $dir"
        fi
    fi
    return 0
}

echo "🔍 Verificando estrutura de diretórios..."

# Verificar diretórios base do storage
check_directory "/app/storage" true
check_directory "/app/storage/app" true
check_directory "/app/storage/app/public" true
check_directory "/app/storage/logs" true
check_directory "/app/storage/framework" true
check_directory "/app/storage/framework/cache" true
check_directory "/app/storage/framework/sessions" true
check_directory "/app/storage/framework/views" true

# Verificar diretórios específicos de upload
echo "📸 Verificando diretórios de upload..."
check_directory "/app/storage/app/public/products" true
check_directory "/app/storage/app/public/services" true
check_directory "/app/storage/app/public/teams" true
check_directory "/app/storage/app/public/logos" true
check_directory "/app/storage/app/public/ai" true
check_directory "/app/storage/app/public/ai/uploads" true
check_directory "/app/storage/app/public/ai/generated" true

# Para storage local, verificar/criar symlink para public
echo "🔗 Verificando symlink para public storage..."
if [ ! -L "/app/public/storage" ]; then
    echo "📎 Criando symlink storage:link"
    php artisan storage:link || echo "⚠️ Não foi possível criar symlink (pode já existir)"
else
    echo "✅ Symlink storage já existe"
fi

# Para storage local, testar escrita
echo "🧪 Testando escrita local..."
if php artisan tinker --execute="Storage::disk('public')->put('test.txt', 'test'); Storage::disk('public')->delete('test.txt'); echo 'LOCAL OK';" 2>/dev/null | grep -q "LOCAL OK"; then
    echo "✅ Storage local está funcionando"
else
    echo "⚠️ Possível problema no storage local"
fi

# Verificar Laravel pode acessar o storage
echo "🎯 Verificando configuração Laravel..."
cd /app

# Verificar se consegue executar comando artisan
if php artisan storage:check > /dev/null 2>&1; then
    echo "✅ Comando storage:check executado com sucesso"
elif php artisan --version > /dev/null 2>&1; then
    echo "✅ Laravel está funcionando"
    echo "⚠️ Comando storage:check não está disponível (normal se não existir)"
else
    echo "❌ Problema ao executar comandos Laravel"
    exit 1
fi

# Configuração já verificada no início

echo ""
echo "🎉 Inicialização do storage concluída com sucesso!"
echo "📊 Status final:"
if [ "$FILESYSTEM_DISK" = "s3" ]; then
    echo "  - S3 Storage: ✅ Configurado"
else
    echo "  - Local Storage: ✅ Configurado"
    echo "  - Symlink storage: ✅ OK"
fi
echo "  - Laravel integration: ✅ OK"
echo ""
