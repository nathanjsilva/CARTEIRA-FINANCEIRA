# SDD: Carteira Financeira — Contexto Geral

> Referência cruzada: [[SDD_ARQUITETURA_ALVO]] | [[SDD_ROTEIRO_REFATORACAO]]

---

## 1. Sumário Executivo

Sistema de carteira financeira digital desenvolvido em Laravel 12 + Vue 3. Permite que usuários criem contas, depositem, saquem e transfiram saldo entre carteiras, além de solicitar reversão de transferências. A arquitetura está parcialmente migrada para DDD, com camadas Domain e Application já estruturadas, mas com inconsistências híbridas onde código legado coexiste com o novo design.

---

## 2. Stack Tecnológico Atual

| Camada | Tecnologia | Versão |
|--------|-----------|--------|
| Runtime | PHP | ^8.2 |
| Framework Backend | Laravel | ^12.0 |
| Autenticação API | Laravel Sanctum | ^4.2 |
| Banco de Dados | MySQL | 8.0 |
| Cache / Queue | Redis | alpine (via predis ^3.2) |
| Activity Log | spatie/laravel-activitylog | ^4.10 |
| Permissões | spatie/laravel-permission | ^6.21 |
| Framework Frontend | Vue.js | 3 (Composition API) |
| Bundler Frontend | Vite | (via npm) |
| Estado Frontend | Pinia | — |
| Estilo Frontend | Tailwind CSS | — |
| Containerização | Docker + docker-compose | — |
| Proxy HTTP | Nginx | stable-alpine |
| PHP-FPM | PHP | 8.2-fpm |
| Admin BD | phpMyAdmin | — |
| Testes | PHPUnit | ^11.5 |

---

## 3. Estrutura de Diretórios

```
carteira-financeira/
├── backend/
│   ├── app/
│   │   ├── Application/
│   │   │   ├── DTOs/               ← 5 DTOs de request/response
│   │   │   └── Services/           ← DepositService, TransferService, WithdrawService
│   │   ├── Domain/
│   │   │   ├── Entities/           ← User, Transaction (puras, sem Eloquent)
│   │   │   ├── Exceptions/         ← DomainException, InsufficientFundsException, UserNotFoundException
│   │   │   ├── Repositories/       ← Interfaces UserRepository, TransactionRepository
│   │   │   └── ValueObjects/       ← Money, Email
│   │   ├── Http/Controllers/Api/   ← ⚠️ Controllers legados (duplicatas)
│   │   ├── Infrastructure/
│   │   │   ├── Http/Controllers/Api/V1/  ← Controllers ativos (Auth, Wallet, Transaction)
│   │   │   └── Repositories/       ← EloquentUserRepository, EloquentTransactionRepository
│   │   ├── Models/                 ← Eloquent: User, Wallet, Transaction, TransactionReversal
│   │   ├── Observers/              ← UserObserver, WalletObserver, TransactionObserver, TransactionReversalObserver
│   │   ├── Providers/              ← AppServiceProvider (bind DI + register Observers)
│   │   └── Services/               ← ⚠️ TransactionReversalService (fora do DDD)
│   ├── database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php                 ← Rotas v1 + rotas legadas (duplicadas)
│   └── tests/
│       ├── Feature/Api/            ← Auth, Wallet, Transaction, Integration tests
│       └── Unit/Domain/            ← Entities, ValueObjects, Observers
├── frontend/
│   └── src/
│       ├── pages/                  ← Login, Register, Dashboard, Transactions
│       └── stores/                 ← auth, wallet (Pinia)
└── docker-compose.yml
```

---

## 4. Modelos de Dados

### Tabela: `users`
| Coluna | Tipo | Observação |
|--------|------|-----------|
| id | bigint PK | Auto-increment |
| name | varchar | — |
| email | varchar | unique |
| password | varchar | bcrypt hashed |
| email_verified_at | timestamp | nullable |
| remember_token | varchar | nullable |
| created_at / updated_at | timestamp | — |

### Tabela: `wallets`
| Coluna | Tipo | Observação |
|--------|------|-----------|
| id | bigint PK | Auto-increment (hidden em JSON) |
| uuid | char(36) | unique, exposto externamente |
| user_id | FK → users | cascade delete |
| balance | decimal(15,2) | Saldo atual |
| currency | varchar(3) | Default: 'BRL' |
| is_active | boolean | Default: true |
| created_at / updated_at | timestamp | — |

### Tabela: `transactions`
| Coluna | Tipo | Observação |
|--------|------|-----------|
| id | bigint PK | Auto-increment (hidden) |
| uuid | char(36) | unique, identificador público |
| from_wallet_id | FK → wallets | nullable |
| to_wallet_id | FK → wallets | nullable |
| type | enum | deposit, withdrawal, transfer, reversal |
| amount | decimal(15,2) | — |
| currency | varchar(3) | Default: 'BRL' |
| status | enum | pending, completed, failed, reversed |
| description | text | nullable |
| metadata | json | nullable |
| reference_id | varchar | unique, nullable (aponta para uuid original na reversão) |
| processed_at | timestamp | nullable |

### Tabela: `transaction_reversals`
| Coluna | Tipo | Observação |
|--------|------|-----------|
| id | bigint PK | — |
| uuid | char(36) | unique |
| original_transaction_id | FK → transactions | — |
| reversal_transaction_id | FK → transactions | — |
| requested_by | FK → users | — |
| reason | enum | user_request, system_error, fraud_detection, compliance |
| description | text | nullable |
| status | enum | pending, approved, rejected, completed |
| approved_by | FK → users | nullable |
| approved_at | timestamp | nullable |

### Tabela: `activity_log`
Gerenciada pelo Spatie Activity Log. Registra mudanças em User (name, email), Wallet (balance, is_active) e Transaction (status, amount, type).

### Relacionamentos
```
User ──< Wallet ──< Transaction (from_wallet_id)
                 ──< Transaction (to_wallet_id)
Transaction ──── TransactionReversal (original_transaction_id)
Transaction ──── TransactionReversal (reversal_transaction_id)
User ──────────── TransactionReversal (requested_by)
User ──────────── TransactionReversal (approved_by)
```

---

## 5. Endpoints da API

### Públicos
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/health` | Health check — retorna `{status: "ok", version: "v1"}` |
| POST | `/api/v1/auth/register` | Registro de usuário. Cria wallet BRL automaticamente via Observer |
| POST | `/api/v1/auth/login` | Login. Retorna Bearer token (Sanctum) |

### Protegidos (Bearer token)
| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/api/v1/auth/logout` | Invalida token atual |
| GET | `/api/v1/auth/me` | Dados do usuário autenticado + wallet |
| GET | `/api/v1/wallet/balance` | Saldo da wallet BRL |
| POST | `/api/v1/wallet/deposit` | Depositar valor |
| POST | `/api/v1/wallet/withdraw` | Sacar valor |
| GET | `/api/v1/wallet/history` | Histórico de transações (param: `limit`, default 50) |
| POST | `/api/v1/transactions/transfer` | Transferir para outro usuário |
| POST | `/api/v1/transactions/reversal/request` | Solicitar reversão de transferência |
| POST | `/api/v1/transactions/reversal/{uuid}/approve` | Aprovar reversão |
| POST | `/api/v1/transactions/reversal/{uuid}/reject` | Rejeitar reversão |

> **Nota:** Rotas legadas sem prefixo `/v1` existem duplicadas em `routes/api.php` para backward compatibility.

---

## 6. Fluxos Críticos

### Fluxo: Registro de Usuário
```
POST /v1/auth/register
    → AuthController::register()
    → User::create() [Eloquent]
    → UserObserver::created() → user->createDefaultWallet()
    → Sanctum token gerado
    ← { user, wallet, token }
```

### Fluxo: Depósito
```
POST /v1/wallet/deposit { amount, description? }
    → WalletController::deposit()
    → DepositService::execute(DepositRequestDTO)
        → UserRepository::findById()  [EloquentUserRepository → domain User]
        → Money::of(amount)
        → DB::transaction {
            user->deposit(amount)       [domain User muta balance]
            UserRepository::save()      [atualiza wallet.balance no Eloquent]
            Transaction::deposit()      [cria domain Transaction]
            TransactionRepository::save()
          }
    ← TransactionResponseDTO → JSON 201
```

### Fluxo: Transferência
```
POST /v1/transactions/transfer { recipient_id, amount, description? }
    → TransactionController::transfer()
    → TransferService::execute(TransferRequestDTO)
        → UserRepository::findById(senderId)
        → UserRepository::findById(recipientId)
        → Money::of(amount)
        → sender.canTransfer(amount) → InsufficientFundsException se falhar
        → DB::transaction {
            sender.transfer(amount, recipient)  [muta ambos os domain Users]
            UserRepository::save(sender)
            UserRepository::save(recipient)
            Transaction::transfer() → transaction.complete()
            TransactionRepository::save()
          }
    ← TransactionResponseDTO → JSON 201
```

### Fluxo: Reversão de Transferência
```
POST /v1/transactions/reversal/request { transaction_id (uuid), reason, description? }
    → TransactionController::requestReversal()
    → Busca Transaction Eloquent diretamente (⚠️ bypass domain)
    → Verifica se user é dono da transação via wallet
    → TransactionReversalService::requestReversal()
        → DB::transaction {
            Cria Transaction (type=reversal, status=pending, wallets invertidas)
            Cria TransactionReversal (status=pending)
          }

POST /v1/transactions/reversal/{uuid}/approve
    → TransactionReversalService::approveReversal()
        → DB::transaction {
            reversal.approve(approver)
            executeReversal(): toWallet.withdraw() + fromWallet.deposit()
            originalTransaction.markAsReversed()
            reversalTransaction.markAsCompleted()
            reversal.markAsCompleted()
          }
```

---

## 7. Padrões Já Implementados

- **Repository Pattern**: Interfaces em `Domain/Repositories/`, implementações Eloquent em `Infrastructure/Repositories/`
- **Service Layer (Application)**: `DepositService`, `TransferService`, `WithdrawService` com DTOs de entrada/saída
- **Value Objects imutáveis**: `Money` (com arredondamento 2 casas, operações safe), `Email`
- **Domain Entities puras**: `User` e `Transaction` sem dependência de framework
- **Observer Pattern**: 4 Observers registrados via `AppServiceProvider` para side-effects (log, wallet criação)
- **Activity Log**: Spatie rastreia mudanças nos 3 modelos principais
- **UUID público / ID interno**: Modelos expõem `uuid` externamente, `id` interno fica oculto
- **DI Container**: `AppServiceProvider` faz bind de interfaces → implementações Eloquent
- **Docker**: Ambiente completo (app, nginx, MySQL, Redis, phpMyAdmin) em `docker-compose.yml`
- **Testes**: Unit (Domain), Feature (API endpoints), Integration tests

---

## 8. Problemas Identificados

| # | Problema | Severidade | Localização |
|---|----------|-----------|-------------|
| 1 | `app/Http/Controllers/Api/` (legado) coexiste com `Infrastructure/Http/Controllers/` | Média | routes/api.php |
| 2 | `TransactionReversalService` fora do Application layer — em `app/Services/` | Alta | app/Services/ |
| 3 | `AuthController` usa Eloquent diretamente — sem domain entity nem Application Service | Alta | Infrastructure/Http/Controllers |
| 4 | `requestReversal()` acessa Eloquent diretamente no Controller (bypassa domain) | Alta | TransactionController |
| 5 | `Money` usa `float` — risco de imprecisão em cálculos financeiros | Alta | Domain/ValueObjects/Money.php |
| 6 | `EloquentUserRepository::save()` só atualiza balance — não persiste novos usuários | Média | Infrastructure/Repositories |
| 7 | `WalletController::balance()` expõe `id` interno em vez de `uuid` | Baixa | Infrastructure/Http/Controllers |
| 8 | Rotas legadas duplicadas em `api.php` sem versionamento claro | Baixa | routes/api.php |
| 9 | `Email` ValueObject definido mas não usado na Entity `User` | Baixa | Domain/Entities/User.php |
| 10 | Observer de Transaction permite transição `failed → pending` (estado suspeito) | Média | Observers/TransactionObserver.php |
| 11 | Validação inline nos Controllers — sem FormRequest classes | Baixa | Todos os Controllers |
| 12 | Sem FormRequest → sem documentação OpenAPI automática | Baixa | — |

---

## 9. Frontend Vue 3

**Páginas:**
- `LoginPage.vue` — Formulário de login, redireciona para dashboard
- `RegisterPage.vue` — Cadastro de usuário
- `DashboardPage.vue` — Saldo atual, ações (depositar/sacar/transferir), últimas 5 transações
- `TransactionsPage.vue` — Histórico completo de transações

**Stores Pinia:**
- `auth` — login, logout, persistência de token e dados do usuário
- `wallet` — balance, transactions, deposit/withdraw/transfer

**Comunicação:** Axios com base URL `http://localhost:8000/api/v1`

---

## 10. Ambiente Docker

```
carteira_api       → PHP-FPM 8.2 (porta interna)
carteira_nginx     → Nginx → :8000
carteira_frontend  → Node 22 + Vite → :5173
carteira_db        → MySQL 8.0 → :3306
carteira_redis     → Redis → :6379
carteira_phpmyadmin → phpMyAdmin → :8080
```

**Banco:** `carteira_financeira` | **Usuário:** `nathan_carteira`
