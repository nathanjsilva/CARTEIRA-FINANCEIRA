#!/bin/bash

# Script para configurar o ambiente Docker

echo "Configurando ambiente Docker..."

# Copiar .env.example para .env se não existir
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Arquivo .env criado"
fi

# Configurar variáveis para Docker
echo "Configurando variáveis de ambiente para Docker..."

# Usar sed para substituir as configurações
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=db/' .env
sed -i 's/# DB_PORT=3306/DB_PORT=3306/' .env
sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=wallet_db/' .env
sed -i 's/# DB_USERNAME=root/DB_USERNAME=wallet_user/' .env
sed -i 's/# DB_PASSWORD=/DB_PASSWORD=wallet_password/' .env

sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=redis/' .env
sed -i 's/QUEUE_CONNECTION=database/QUEUE_CONNECTION=redis/' .env
sed -i 's/CACHE_STORE=database/CACHE_STORE=redis/' .env
sed -i 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/' .env

echo "Configuração concluída!"
echo "Execute: docker-compose up -d --build"


