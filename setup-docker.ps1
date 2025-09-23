# Script PowerShell para configurar o ambiente Docker

Write-Host "Configurando ambiente Docker..." -ForegroundColor Green

# Copiar .env.example para .env se não existir
if (!(Test-Path .env)) {
    Copy-Item .env.example .env
    Write-Host "Arquivo .env criado" -ForegroundColor Yellow
}

# Configurar variáveis para Docker
Write-Host "Configurando variáveis de ambiente para Docker..." -ForegroundColor Green

# Ler o conteúdo do .env
$envContent = Get-Content .env

# Substituir as configurações
$envContent = $envContent -replace 'DB_CONNECTION=sqlite', 'DB_CONNECTION=mysql'
$envContent = $envContent -replace '# DB_HOST=127.0.0.1', 'DB_HOST=db'
$envContent = $envContent -replace '# DB_PORT=3306', 'DB_PORT=3306'
$envContent = $envContent -replace '# DB_DATABASE=laravel', 'DB_DATABASE=wallet_db'
$envContent = $envContent -replace '# DB_USERNAME=root', 'DB_USERNAME=wallet_user'
$envContent = $envContent -replace '# DB_PASSWORD=', 'DB_PASSWORD=wallet_password'

$envContent = $envContent -replace 'SESSION_DRIVER=database', 'SESSION_DRIVER=redis'
$envContent = $envContent -replace 'QUEUE_CONNECTION=database', 'QUEUE_CONNECTION=redis'
$envContent = $envContent -replace 'CACHE_STORE=database', 'CACHE_STORE=redis'
$envContent = $envContent -replace 'REDIS_HOST=127.0.0.1', 'REDIS_HOST=redis'

# Salvar o arquivo .env
$envContent | Set-Content .env

Write-Host "Configuração concluída!" -ForegroundColor Green
Write-Host "Execute: docker-compose up -d --build" -ForegroundColor Cyan


