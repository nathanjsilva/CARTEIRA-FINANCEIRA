# 🐳 Comandos Docker e PHP - Guia Completo

## 📋 **Visão Geral**

Este arquivo contém todos os comandos necessários para executar o projeto, desde a instalação até a manutenção. Cada comando é explicado detalhadamente para que você saiba exatamente o que está fazendo.

---

## 🚀 **Comandos de Inicialização**

### **1. Primeira Execução (Setup Inicial)**

#### **Clonar o Repositório:**
```bash
git clone <url-do-repositorio>
cd api-carteira-financeira
```

#### **Configurar Ambiente (PowerShell):**
```bash
# Copiar arquivo de ambiente
cp .env.example .env

# Configurar automaticamente para Docker
(Get-Content .env) -replace 'DB_CONNECTION=sqlite', 'DB_CONNECTION=mysql' -replace '# DB_HOST=127.0.0.1', 'DB_HOST=db' -replace '# DB_PORT=3306', 'DB_PORT=3306' -replace '# DB_DATABASE=laravel', 'DB_DATABASE=wallet_db' -replace '# DB_USERNAME=root', 'DB_USERNAME=wallet_user' -replace '# DB_PASSWORD=', 'DB_PASSWORD=wallet_password' -replace 'SESSION_DRIVER=database', 'SESSION_DRIVER=redis' -replace 'QUEUE_CONNECTION=database', 'QUEUE_CONNECTION=redis' -replace 'CACHE_STORE=database', 'CACHE_STORE=redis' -replace 'REDIS_HOST=127.0.0.1', 'REDIS_HOST=redis' | Set-Content .env
```

**O que faz:**
- Substitui configurações do SQLite por MySQL
- Configura host do banco para `db` (container Docker)
- Configura Redis para cache e sessões
- Define credenciais do banco de dados

#### **Construir e Executar Containers:**
```bash
# Construir e executar containers em background
docker-compose up -d --build

# Ou executar em foreground (para ver logs)
docker-compose up --build
```

**O que faz:**
- `-d`: Executa em background (detached mode)
- `--build`: Reconstrói as imagens se necessário
- Cria e inicia todos os containers: app, nginx, db, redis, phpmyadmin

---

## 🔧 **Comandos de Desenvolvimento**

### **2. Instalar Dependências**

#### **Instalar Dependências PHP:**
```bash
docker-compose exec app composer install
```

**O que faz:**
- Executa `composer install` dentro do container `app`
- Instala todas as dependências PHP listadas no `composer.json`

#### **Instalar Dependências Node.js (se necessário):**
```bash
docker-compose exec app npm install
```

**O que faz:**
- Executa `npm install` dentro do container `app`
- Instala dependências JavaScript para frontend

### **3. Configurar Aplicação**

#### **Gerar Chave da Aplicação:**
```bash
docker-compose exec app php artisan key:generate
```

**O que faz:**
- Gera chave única para a aplicação Laravel
- Necessário para criptografia e segurança

#### **Limpar Cache:**
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

**O que faz:**
- `cache:clear`: Limpa cache da aplicação
- `config:clear`: Limpa cache de configuração
- `route:clear`: Limpa cache de rotas
- `view:clear`: Limpa cache de views

---

## 🗄️ **Comandos de Banco de Dados**

### **4. Migrations**

#### **Executar Migrations:**
```bash
docker-compose exec app php artisan migrate
```

**O que faz:**
- Executa todas as migrations pendentes
- Cria tabelas no banco de dados
- **IMPORTANTE**: Execute sempre após mudanças nas migrations

#### **Executar Migrations com Seeders:**
```bash
docker-compose exec app php artisan migrate --seed
```

**O que faz:**
- Executa migrations e popula o banco com dados iniciais
- Útil para desenvolvimento e testes

#### **Resetar Banco (CUIDADO!):**
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

**O que faz:**
- `migrate:fresh`: Dropa todas as tabelas e recria
- `--seed`: Popula com dados iniciais
- **CUIDADO**: Apaga todos os dados existentes!

#### **Rollback de Migrations:**
```bash
# Rollback da última migration
docker-compose exec app php artisan migrate:rollback

# Rollback de todas as migrations
docker-compose exec app php artisan migrate:reset

# Ver status das migrations
docker-compose exec app php artisan migrate:status
```

**O que faz:**
- `rollback`: Desfaz a última migration executada
- `reset`: Desfaz todas as migrations
- `status`: Mostra quais migrations foram executadas

### **5. Seeders**

#### **Executar Seeders:**
```bash
docker-compose exec app php artisan db:seed
```

**O que faz:**
- Executa todos os seeders
- Popula o banco com dados de teste

#### **Executar Seeder Específico:**
```bash
docker-compose exec app php artisan db:seed --class=DatabaseSeeder
```

**O que faz:**
- Executa apenas o seeder especificado
- Útil para testar seeders específicos

---

## 🧪 **Comandos de Testes**

### **6. Executar Testes**

#### **Executar Todos os Testes:**
```bash
docker-compose exec app php artisan test
```

**O que faz:**
- Executa todos os testes (unitários e de integração)
- Mostra resultado de todos os testes

#### **Executar Testes com Verbose:**
```bash
docker-compose exec app php artisan test --verbose
```

**O que faz:**
- Executa testes com informações detalhadas
- Mostra mais detalhes sobre cada teste

#### **Executar Testes com Cobertura:**
```bash
docker-compose exec app php artisan test --coverage
```

**O que faz:**
- Executa testes e gera relatório de cobertura
- Mostra quais partes do código foram testadas

#### **Executar Testes Específicos:**
```bash
# Testes unitários
docker-compose exec app php artisan test tests/Unit/

# Testes de integração
docker-compose exec app php artisan test tests/Feature/

# Teste específico
docker-compose exec app php artisan test tests/Feature/Api/AuthControllerTest.php

# Teste específico com filtro
docker-compose exec app php artisan test --filter=test_user_can_register
```

**O que faz:**
- Executa apenas os testes especificados
- Útil para testar funcionalidades específicas

---

## 📊 **Comandos de Monitoramento**

### **7. Logs**

#### **Ver Logs da Aplicação:**
```bash
docker-compose logs -f app
```

**O que faz:**
- `-f`: Segue os logs em tempo real
- Mostra logs do container `app` (PHP/Laravel)

#### **Ver Logs de Todos os Containers:**
```bash
docker-compose logs -f
```

**O que faz:**
- Mostra logs de todos os containers
- Útil para debug geral

#### **Ver Logs de Container Específico:**
```bash
docker-compose logs -f nginx
docker-compose logs -f db
docker-compose logs -f redis
```

**O que faz:**
- Mostra logs de container específico
- Útil para debug de problemas específicos

#### **Ver Logs do Laravel:**
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

**O que faz:**
- Mostra logs do Laravel em tempo real
- Útil para debug de aplicação

### **8. Status dos Containers**

#### **Ver Status dos Containers:**
```bash
docker-compose ps
```

**O que faz:**
- Mostra status de todos os containers
- Indica se estão rodando, parados, etc.

#### **Ver Uso de Recursos:**
```bash
docker stats
```

**O que faz:**
- Mostra uso de CPU, memória e rede
- Útil para monitorar performance

---

## 🔄 **Comandos de Manutenção**

### **9. Reiniciar Serviços**

#### **Reiniciar Todos os Containers:**
```bash
docker-compose restart
```

**O que faz:**
- Reinicia todos os containers
- Útil após mudanças de configuração

#### **Reiniciar Container Específico:**
```bash
docker-compose restart app
docker-compose restart nginx
docker-compose restart db
```

**O que faz:**
- Reinicia apenas o container especificado
- Útil para aplicar mudanças específicas

#### **Parar Todos os Containers:**
```bash
docker-compose down
```

**O que faz:**
- Para e remove todos os containers
- **CUIDADO**: Dados do banco podem ser perdidos se não estiverem em volume

#### **Parar e Remover Tudo:**
```bash
docker-compose down -v
```

**O que faz:**
- `-v`: Remove volumes também
- **CUIDADO**: Apaga dados do banco de dados!

---

## 🛠️ **Comandos de Desenvolvimento Avançado**

### **10. Acessar Container**

#### **Acessar Container da Aplicação:**
```bash
docker-compose exec app bash
```

**O que faz:**
- Abre shell dentro do container `app`
- Permite executar comandos diretamente no container

#### **Acessar Container do Banco:**
```bash
docker-compose exec db mysql -u wallet_user -p wallet_db
```

**O que faz:**
- Abre MySQL dentro do container `db`
- Permite executar queries SQL diretamente

### **11. Comandos Artisan Úteis**

#### **Listar Rotas:**
```bash
docker-compose exec app php artisan route:list
```

**O que faz:**
- Lista todas as rotas da aplicação
- Útil para verificar rotas disponíveis

#### **Limpar Tudo:**
```bash
docker-compose exec app php artisan optimize:clear
```

**O que faz:**
- Limpa todos os caches da aplicação
- Útil quando a aplicação está com comportamento estranho

#### **Gerar Arquivos:**
```bash
# Gerar controller
docker-compose exec app php artisan make:controller NomeController

# Gerar model
docker-compose exec app php artisan make:model NomeModel

# Gerar migration
docker-compose exec app php artisan make:migration nome_da_migration

# Gerar seeder
docker-compose exec app php artisan make:seeder NomeSeeder
```

**O que faz:**
- Cria arquivos básicos do Laravel
- Útil para desenvolvimento de novas funcionalidades

---

## 🚨 **Comandos de Emergência**

### **12. Resolver Problemas**

#### **Recriar Containers:**
```bash
docker-compose down
docker-compose up -d --build --force-recreate
```

**O que faz:**
- `--force-recreate`: Força recriação dos containers
- Útil quando containers estão com problemas

#### **Limpar Docker:**
```bash
# Remover containers parados
docker container prune

# Remover imagens não utilizadas
docker image prune

# Remover volumes não utilizados
docker volume prune

# Limpar tudo (CUIDADO!)
docker system prune -a
```

**O que faz:**
- Remove recursos Docker não utilizados
- Libera espaço em disco

#### **Resetar Projeto Completamente:**
```bash
# Parar tudo
docker-compose down -v

# Remover imagens
docker-compose down --rmi all

# Recriar tudo
docker-compose up -d --build

# Reconfigurar aplicação
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate:fresh --seed
```

**O que faz:**
- Reset completo do projeto
- **CUIDADO**: Apaga todos os dados!

---

## 📋 **Checklist de Execução**

### **Para Primeira Execução:**
```bash
# 1. Clone o repositório
git clone <url>
cd api-carteira-financeira

# 2. Configure o ambiente
cp .env.example .env
# [Configure o .env manualmente ou use o comando PowerShell]

# 3. Execute os containers
docker-compose up -d --build

# 4. Instale dependências
docker-compose exec app composer install

# 5. Configure a aplicação
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate:fresh --seed

# 6. Teste a aplicação
docker-compose exec app php artisan test
```

### **Para Desenvolvimento Diário:**
```bash
# 1. Inicie os containers
docker-compose up -d

# 2. Execute testes
docker-compose exec app php artisan test

# 3. Verifique logs se necessário
docker-compose logs -f app
```

### **Para Deploy/Produção:**
```bash
# 1. Configure variáveis de ambiente para produção
# 2. Execute containers
docker-compose up -d --build

# 3. Configure aplicação
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan optimize

# 4. Verifique status
docker-compose ps
docker-compose exec app php artisan test
```

---

## 🎯 **Dicas Importantes**

### **Sempre Execute:**
- `docker-compose exec app php artisan migrate` após mudanças no banco
- `docker-compose exec app php artisan test` antes de commitar
- `docker-compose logs -f app` quando algo não funcionar

### **Nunca Execute em Produção:**
- `docker-compose down -v` (apaga dados)
- `php artisan migrate:fresh` (apaga dados)
- `docker system prune -a` (pode apagar imagens importantes)

### **Para Debug:**
- Use `docker-compose logs -f` para ver logs
- Use `docker-compose exec app bash` para acessar container
- Use `docker-compose ps` para ver status

### **Para Performance:**
- Use `docker-compose up -d` para executar em background
- Use `docker stats` para monitorar recursos
- Use cache do Laravel para melhor performance

---

## 📞 **Suporte**

Se algo não funcionar:

1. **Verifique logs**: `docker-compose logs -f app`
2. **Verifique status**: `docker-compose ps`
3. **Reinicie containers**: `docker-compose restart`
4. **Recrie containers**: `docker-compose up -d --build --force-recreate`
5. **Reset completo**: Use comandos de emergência (com cuidado!)

---

*Este arquivo contém todos os comandos necessários para executar e manter o projeto. Mantenha-o sempre atualizado e use como referência.*
