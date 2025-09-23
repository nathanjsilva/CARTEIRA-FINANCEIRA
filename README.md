<<<<<<< HEAD
# Sistema de Carteira Financeira

Sistema completo de carteira financeira desenvolvido em Laravel 12 com PHP 8.2, MySQL e Docker. Permite que usuários realizem operações de depósito, saque, transferência e reversão de transações com total segurança e auditoria.

## 🚀 Funcionalidades

- ✅ **Cadastro e Autenticação** com Laravel Sanctum
- ✅ **Operações de Carteira**: Depósito, Saque e Transferência
- ✅ **Sistema de Reversão** de transações
- ✅ **Validação de Saldo** antes de operações
- ✅ **Auditoria Completa** com logs de atividade
- ✅ **API RESTful** com documentação completa
- ✅ **Docker** para ambiente de desenvolvimento
- ✅ **Testes** unitários e de integração
- ✅ **Observabilidade** com logging estruturado

## 🛠️ Tecnologias

- **Backend**: Laravel 12, PHP 8.2
- **Banco de Dados**: MySQL 8.0
- **Cache/Sessão**: Redis
- **Autenticação**: Laravel Sanctum
- **Containerização**: Docker & Docker Compose
- **Logging**: Spatie Activity Log
- **Testes**: PHPUnit

## 📋 Requisitos

- Docker e Docker Compose
- PHP 8.2+
- Composer
- MySQL 8.0+
- Redis

## 🚀 Instalação e Execução

### 1. Clone o repositório
```bash
git clone <url-do-repositorio>
cd api-carteira-financeira
```

### 2. Configure o ambiente
```bash
# Copie o arquivo de ambiente
cp .env.example .env

# Configure as variáveis de ambiente no .env para Docker:
# (Execute o comando abaixo para configurar automaticamente)
# PowerShell:
(Get-Content .env) -replace 'DB_CONNECTION=sqlite', 'DB_CONNECTION=mysql' -replace '# DB_HOST=127.0.0.1', 'DB_HOST=db' -replace '# DB_PORT=3306', 'DB_PORT=3306' -replace '# DB_DATABASE=laravel', 'DB_DATABASE=wallet_db' -replace '# DB_USERNAME=root', 'DB_USERNAME=wallet_user' -replace '# DB_PASSWORD=', 'DB_PASSWORD=wallet_password' -replace 'SESSION_DRIVER=database', 'SESSION_DRIVER=redis' -replace 'QUEUE_CONNECTION=database', 'QUEUE_CONNECTION=redis' -replace 'CACHE_STORE=database', 'CACHE_STORE=redis' -replace 'REDIS_HOST=127.0.0.1', 'REDIS_HOST=redis' | Set-Content .env

# Ou configure manualmente no .env:
# DB_CONNECTION=mysql
# DB_HOST=db
# DB_PORT=3306
# DB_DATABASE=carteira_financeira
# DB_USERNAME=nathan_carteira
# DB_PASSWORD=
# REDIS_HOST=redis
# CACHE_STORE=redis
# SESSION_DRIVER=redis
# QUEUE_CONNECTION=redis
```

### 3. Execute com Docker
```bash
# Construa e execute os containers
docker-compose up -d --build

# Instale as dependências
docker-compose exec app composer install

# Gere a chave da aplicação
docker-compose exec app php artisan key:generate

# Execute as migrations
docker-compose exec app php artisan migrate

# (Opcional) Execute os seeders
docker-compose exec app php artisan db:seed
```

### 4. Acesse a aplicação
- **API**: http://localhost:8000/api
- **PhpMyAdmin**: http://localhost:8080
- **Logs**: `docker-compose logs -f app`

## 📚 Documentação

- **[Guia do Postman](POSTMAN_GUIDE.md)** - Como usar a API com Postman
- **Postman Collection**: `postman_collection.json` - Collection completa para testes
- **Postman Environment**: `postman_environment.json` - Ambiente configurado

## 🧪 Testes

```bash
# Execute os testes
docker-compose exec app php artisan test

# Execute com cobertura
docker-compose exec app php artisan test --coverage
```

## 📊 Estrutura do Banco de Dados

### Tabelas Principais

- **users**: Usuários do sistema
- **wallets**: Carteiras dos usuários
- **transactions**: Transações financeiras
- **transaction_reversals**: Reversões de transações
- **activity_log**: Logs de auditoria

### Relacionamentos

- User → hasMany → Wallets
- Wallet → hasMany → Transactions (sent/received)
- Transaction → hasOne → TransactionReversal
- Todas as operações são auditadas automaticamente

## 🔐 Segurança

- **Autenticação**: Laravel Sanctum com tokens seguros
- **Validação**: Validação rigorosa de todos os inputs
- **Transações**: Operações atômicas com rollback automático
- **Auditoria**: Logs completos de todas as operações
- **Autorização**: Verificação de permissões em todas as operações

## 🏗️ Arquitetura

O sistema segue os princípios **SOLID** e implementa:

- **Service Layer Pattern**: Lógica de negócio isolada
- **Repository Pattern**: Abstração do banco de dados
- **Observer Pattern**: Logs automáticos de mudanças
- **Factory Pattern**: Criação de dados de teste

## 📈 Performance

- **Índices otimizados** para queries frequentes
- **Cache Redis** para sessões e dados frequentes
- **Eager Loading** para evitar N+1 queries
- **Transações atômicas** para consistência

## 🔄 Fluxo de Operações

### Depósito
1. Validação do valor
2. Criação da transação
3. Atualização do saldo
4. Log de auditoria

### Transferência
1. Validação de carteiras
2. Verificação de saldo
3. Transação atômica (debit + credit)
4. Log de auditoria

### Reversão
1. Solicitação de reversão
2. Aprovação manual
3. Execução da reversão
4. Log de auditoria

## 🐳 Docker

O projeto inclui configuração completa do Docker:

- **app**: Container PHP-FPM com Laravel
- **nginx**: Servidor web
- **db**: MySQL 8.0
- **redis**: Cache e sessões
- **phpmyadmin**: Interface web para MySQL

## 📝 Exemplos de Uso

### Registrar Usuário
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "senha123",
    "password_confirmation": "senha123"
  }'
```

### Fazer Depósito
```bash
curl -X POST http://localhost:8000/api/wallet/deposit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "amount": 1000.00,
    "description": "Depósito inicial"
  }'
```

### Transferir
```bash
curl -X POST http://localhost:8000/api/wallet/transfer \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "to_wallet_id": 2,
    "amount": 100.00,
    "description": "Transferência para Maria"
  }'
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👨‍💻 Autor

**nathanjsilva**
- 📧 **Email**: nathan.ads.100@gmail.com
- 🚀 **Desenvolvedor Full Stack**

=======
# api-carteira-financeira
>>>>>>> 842373fce101086609351db57a4be06c10f475b0
