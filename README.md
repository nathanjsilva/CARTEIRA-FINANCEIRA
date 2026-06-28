# Carteira Financeira

Sistema de carteira financeira digital full-stack, desenvolvido com **Laravel 12** no backend e **Vue 3** no frontend. Permite que usuários realizem operações financeiras — depósito, saque, transferência e reversão de transações — com auditoria completa e arquitetura DDD.

---

## Funcionalidades

| Funcionalidade | Descrição |
|----------------|-----------|
| Autenticação | Registro, login e logout com Laravel Sanctum (Bearer token) |
| Carteira | Consulta de saldo e histórico de transações |
| Depósito | Adiciona saldo à carteira do usuário |
| Saque | Remove saldo com validação de fundos suficientes |
| Transferência | Transfere saldo entre usuários do sistema de forma atômica |
| Reversão | Solicita estorno de uma transferência com aprovação manual |
| Extrato PDF | Baixa comprovante em PDF de qualquer transação |
| Auditoria | Log automático de todas as operações via Spatie Activity Log |
| Jobs Agendados | Aprovação automática de reversões pendentes via queue Redis |

---

## Stack Tecnológico

### Backend
| Camada | Tecnologia |
|--------|-----------|
| Framework | Laravel 12 / PHP 8.2 |
| Autenticação | Laravel Sanctum |
| Banco de dados | MySQL 8.0 |
| Cache e filas | Redis (Predis) |
| Geração de PDF | barryvdh/laravel-dompdf |
| Auditoria | spatie/laravel-activitylog |
| Testes | PHPUnit 11 |

### Frontend
| Camada | Tecnologia |
|--------|-----------|
| Framework | Vue 3 (Composition API) |
| Estado | Pinia |
| Estilo | Tailwind CSS v4 |
| Roteamento | Vue Router 4 |
| HTTP | Axios |
| Bundler | Vite |

### Infraestrutura
| Serviço | Container |
|---------|-----------|
| API PHP-FPM | `carteira_api` |
| Scheduler (cron) | `carteira_scheduler` |
| Queue Worker | `carteira_queue` |
| Nginx | `carteira_nginx` → porta 8000 |
| Vue 3 / Vite | `carteira_frontend` → porta 5173 |
| MySQL 8.0 | `carteira_db` → porta 3306 |
| Redis | `carteira_redis` → porta 6379 |
| phpMyAdmin | `carteira_phpmyadmin` → porta 8080 |

---

## Arquitetura — DDD

O backend segue **Domain-Driven Design** em 4 camadas com dependências unidirecionais:

```
Presentation  →  Application  →  Domain  ←  Infrastructure
(Requests,       (Services,       (Entities,    (Controllers,
 Resources)       DTOs, Events)    ValueObjects,  Repositories,
                                   Repositories,  Cache, Jobs)
                                   Exceptions)
```

**Regra principal:** o `Domain` não importa nenhuma classe do Laravel. Toda regra de negócio vive em `app/Domain/`, orquestrada por `app/Application/Services/`.

---

## Banco de Dados

### Tabelas

| Tabela | Descrição |
|--------|-----------|
| `users` | Usuários do sistema |
| `wallets` | Carteira BRL de cada usuário (1:1 com users) |
| `transactions` | Registro de todas as movimentações |
| `transaction_reversals` | Estornos com status e aprovação |
| `activity_log` | Auditoria automática de todas as operações |

### Relacionamentos

```
User ──< Wallet ──< Transaction (from_wallet_id)
                 ──< Transaction (to_wallet_id)
Transaction ──── TransactionReversal (original_transaction_id)
Transaction ──── TransactionReversal (reversal_transaction_id)
```

---

## Endpoints da API

Base URL: `http://localhost:8000/api`

### Públicos

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/health` | Health check |
| POST | `/v1/auth/register` | Registro de usuário |
| POST | `/v1/auth/login` | Login — retorna Bearer token |

### Protegidos (Bearer token obrigatório)

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/v1/auth/logout` | Invalida o token atual |
| GET | `/v1/auth/me` | Dados do usuário autenticado + wallet |
| GET | `/v1/wallet/balance` | Saldo da carteira |
| POST | `/v1/wallet/deposit` | Depositar valor |
| POST | `/v1/wallet/withdraw` | Sacar valor |
| GET | `/v1/wallet/history` | Histórico de transações |
| POST | `/v1/transactions/transfer` | Transferir para outro usuário |
| POST | `/v1/transactions/reversal/request` | Solicitar reversão de transferência |
| POST | `/v1/transactions/reversal/{id}/approve` | Aprovar reversão |
| POST | `/v1/transactions/reversal/{id}/reject` | Rejeitar reversão |
| GET | `/v1/transactions/{uuid}/receipt` | Download do comprovante em PDF |

---

## Como Executar

### Pré-requisitos

- Docker e Docker Compose instalados

### 1. Clone o repositório

```bash
git clone <url-do-repositorio>
cd carteira-financeira
```

### 2. Suba os containers

```bash
docker-compose up -d --build
```

### 3. Configure o backend

```bash
# Instale as dependências PHP
docker-compose exec app composer install

# Gere a chave da aplicação
docker-compose exec app php artisan key:generate

# Execute as migrations
docker-compose exec app php artisan migrate
```

### 4. Acesse

| Serviço | URL |
|---------|-----|
| API | http://localhost:8000/api |
| Frontend (Vue 3) | http://localhost:5173 |
| phpMyAdmin | http://localhost:8080 |

---

## Testes

```bash
# Executa todos os testes
docker-compose exec app php artisan test

# Com cobertura de código
docker-compose exec app php artisan test --coverage
```

---

## Exemplos de Uso

### Registrar usuário

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "senha12345",
    "password_confirmation": "senha12345"
  }'
```

### Depositar

```bash
curl -X POST http://localhost:8000/api/v1/wallet/deposit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{"amount": 1000.00, "description": "Depósito inicial"}'
```

### Transferir

```bash
curl -X POST http://localhost:8000/api/v1/transactions/transfer \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{"recipient_id": 2, "amount": 100.00, "description": "Pagamento"}'
```

### Baixar comprovante PDF

```bash
curl -X GET http://localhost:8000/api/v1/transactions/{uuid}/receipt \
  -H "Authorization: Bearer {token}" \
  --output extrato.pdf
```

---

## Segurança

- Tokens Bearer gerenciados pelo Laravel Sanctum (invalidados no logout)
- Nenhum ID interno exposto na API — apenas UUIDs
- Validação de ownership em todas as operações sobre recursos de terceiros
- Operações financeiras executadas dentro de `DB::transaction()` com rollback automático
- Rate limiting nos endpoints de autenticação e transações
- Senhas hasheadas com bcrypt

---

## Autor

**Nathan J. Silva**
- Email: nathan.ads.100@gmail.com
