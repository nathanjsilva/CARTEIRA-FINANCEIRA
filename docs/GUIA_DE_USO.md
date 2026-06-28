# Guia de Uso — Carteira Financeira

## Sumário

1. [Rodando o sistema localmente](#1-rodando-o-sistema-localmente)
2. [Usando o frontend](#2-usando-o-frontend)
3. [API — Autenticação](#3-api--autenticação)
4. [API — Carteira](#4-api--carteira)
5. [API — Transferência](#5-api--transferência)
6. [API — Reversão](#6-api--reversão)
7. [API — Extrato PDF](#7-api--extrato-pdf)
8. [Códigos de erro](#8-códigos-de-erro)

---

## 1. Rodando o sistema localmente

### Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e em execução

### Passo a passo

**1. Clone o repositório**

```bash
git clone https://github.com/nathanjsilva/api-carteira-financeira.git
cd api-carteira-financeira
```

**2. Suba todos os containers**

```bash
docker-compose up -d --build
```

Isso inicia 8 serviços:

| Container | Descrição | Porta |
|-----------|-----------|-------|
| `carteira_nginx` | Proxy HTTP — entrada da API | 8000 |
| `carteira_api` | Laravel (PHP-FPM) | interno |
| `carteira_scheduler` | Agendador de jobs (cron) | interno |
| `carteira_queue` | Worker de filas Redis | interno |
| `carteira_frontend` | Vue 3 / Vite | 5173 |
| `carteira_db` | MySQL 8.0 | 3306 |
| `carteira_redis` | Cache e filas | 6379 |
| `carteira_phpmyadmin` | Administração do banco | 8080 |

**3. Instale as dependências PHP**

```bash
docker-compose exec app composer install
```

**4. Gere a chave da aplicação**

```bash
docker-compose exec app php artisan key:generate
```

**5. Execute as migrations**

```bash
docker-compose exec app php artisan migrate
```

**6. Verifique se está funcionando**

```bash
curl http://localhost:8000/api/health
# { "status": "ok", "version": "v1" }
```

### Acessos

| Serviço | URL |
|---------|-----|
| Frontend (Vue 3) | http://localhost:5173 |
| API | http://localhost:8000/api |
| phpMyAdmin | http://localhost:8080 |

### Credenciais do banco

| Campo | Valor |
|-------|-------|
| Host | localhost:3306 |
| Banco | carteira_financeira |
| Usuário | nathan_carteira |
| Senha | 1234 |
| Root password | 1234 |

### Comandos úteis

```bash
# Ver logs em tempo real
docker-compose logs -f app

# Rodar testes
docker-compose exec app php artisan test

# Acessar o container PHP
docker-compose exec app bash

# Parar todos os containers
docker-compose down

# Parar e remover volumes (apaga o banco)
docker-compose down -v
```

---

## 2. Usando o frontend

O frontend é acessado em **http://localhost:5173** e não requer configuração adicional.

### Tela de registro

Acesse `/register` para criar uma conta nova. Preencha nome, email, senha e confirmação de senha. Ao registrar, uma carteira BRL é criada automaticamente e você é redirecionado para o dashboard.

### Tela de login

Acesse `/login` com email e senha cadastrados. O token de autenticação é salvo localmente e você é redirecionado para o dashboard.

### Dashboard (`/dashboard`)

A tela principal exibe:
- **Saldo atual** da carteira em BRL
- **Ações rápidas**: Depositar, Sacar e Transferir — cada uma abre um modal
- **Últimas 5 transações** com tipo, valor e status

**Realizar uma operação:**
1. Clique em "Depositar", "Sacar" ou "Transferir"
2. Preencha o valor e, opcionalmente, uma descrição
3. Para transferência, informe também o ID do destinatário
4. Confirme — o saldo é atualizado automaticamente

### Histórico de transações (`/transactions`)

Exibe todas as transações da carteira com:
- Busca por texto (descrição ou valor)
- Filtro por tipo: depósito, saque, transferência, reversão

### Download de extrato PDF

Em qualquer transação do histórico, clique no botão de download para baixar o comprovante em PDF. O arquivo contém todos os dados da operação.

### Logout

Clique em "Sair" no menu superior. O token é invalidado no servidor e o usuário é redirecionado para `/login`.

---

## 3. API — Autenticação

Base URL: `http://localhost:8000/api/v1`

> Rotas públicas não exigem token. Rotas protegidas exigem o header `Authorization: Bearer {token}`.

---

### `POST /auth/register` — Registrar usuário

Cria um novo usuário e uma carteira BRL associada. Retorna o token de autenticação.

**Request**

```http
POST http://localhost:8000/api/v1/auth/register
Content-Type: application/json
```

```json
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "senha12345",
  "password_confirmation": "senha12345"
}
```

**Campos**

| Campo | Tipo | Obrigatório | Regras |
|-------|------|-------------|--------|
| name | string | sim | max 255 caracteres |
| email | string | sim | formato válido, único no sistema |
| password | string | sim | mínimo 8 caracteres |
| password_confirmation | string | sim | deve ser igual a `password` |

**Response 201 — Sucesso**

```json
{
  "user": {
    "id": "3f2504e0-4f89-11d3-9a0c-0305e82c3301",
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "wallet": {
    "uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
    "balance": 0.00,
    "currency": "BRL"
  },
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz123456"
}
```

**Response 422 — Erro de validação**

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### `POST /auth/login` — Login

Autentica o usuário e retorna um Bearer token.

**Request**

```http
POST http://localhost:8000/api/v1/auth/login
Content-Type: application/json
```

```json
{
  "email": "joao@example.com",
  "password": "senha12345"
}
```

**Response 200 — Sucesso**

```json
{
  "user": {
    "id": "3f2504e0-4f89-11d3-9a0c-0305e82c3301",
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "wallet": {
    "uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
    "balance": 150.00,
    "currency": "BRL"
  },
  "token": "2|XyZaBcDeFgHiJkLmNoPqRsTuVwXyZ789"
}
```

**Response 401 — Credenciais inválidas**

```json
{
  "message": "Credenciais inválidas."
}
```

---

### `POST /auth/logout` — Logout

Invalida o token atual. Outros dispositivos permanecem autenticados.

**Request**

```http
POST http://localhost:8000/api/v1/auth/logout
Authorization: Bearer {token}
```

**Response 200 — Sucesso**

```json
{
  "message": "Logged out successfully."
}
```

---

### `GET /auth/me` — Dados do usuário autenticado

Retorna os dados do usuário logado e o estado atual da wallet.

**Request**

```http
GET http://localhost:8000/api/v1/auth/me
Authorization: Bearer {token}
```

**Response 200 — Sucesso**

```json
{
  "user": {
    "id": "3f2504e0-4f89-11d3-9a0c-0305e82c3301",
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "wallet": {
    "uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
    "balance": 150.00,
    "currency": "BRL"
  }
}
```

---

## 4. API — Carteira

---

### `GET /wallet/balance` — Consultar saldo

**Request**

```http
GET http://localhost:8000/api/v1/wallet/balance
Authorization: Bearer {token}
```

**Response 200 — Sucesso**

```json
{
  "wallet": {
    "uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
    "balance": 150.00,
    "currency": "BRL",
    "is_active": true
  }
}
```

---

### `POST /wallet/deposit` — Depositar

Adiciona saldo à carteira do usuário autenticado.

**Request**

```http
POST http://localhost:8000/api/v1/wallet/deposit
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "amount": 500.00,
  "description": "Salário de junho"
}
```

**Campos**

| Campo | Tipo | Obrigatório | Regras |
|-------|------|-------------|--------|
| amount | number | sim | mínimo 0.01 |
| description | string | não | max 255 caracteres |

**Response 201 — Sucesso**

```json
{
  "success": true,
  "message": "Depósito realizado com sucesso",
  "data": {
    "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "type": "deposit",
    "amount": 500.00,
    "currency": "BRL",
    "status": "completed",
    "description": "Salário de junho",
    "created_at": "2026-06-28T14:30:00.000000Z"
  }
}
```

---

### `POST /wallet/withdraw` — Sacar

Remove saldo da carteira do usuário autenticado.

**Request**

```http
POST http://localhost:8000/api/v1/wallet/withdraw
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "amount": 100.00,
  "description": "Pagamento de conta"
}
```

**Response 201 — Sucesso**

```json
{
  "success": true,
  "message": "Saque realizado com sucesso",
  "data": {
    "id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
    "type": "withdrawal",
    "amount": 100.00,
    "currency": "BRL",
    "status": "completed",
    "description": "Pagamento de conta",
    "created_at": "2026-06-28T14:35:00.000000Z"
  }
}
```

**Response 422 — Saldo insuficiente**

```json
{
  "success": false,
  "message": "Saldo insuficiente. Disponível: R$ 50,00, solicitado: R$ 100,00."
}
```

---

### `GET /wallet/history` — Histórico de transações

Retorna as transações onde a carteira do usuário é remetente ou destinatária.

**Request**

```http
GET http://localhost:8000/api/v1/wallet/history?limit=50
Authorization: Bearer {token}
```

**Query params**

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| limit | integer | 50 | Número máximo de transações retornadas |

**Response 200 — Sucesso**

```json
{
  "transactions": [
    {
      "id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "type": "deposit",
      "amount": 500.00,
      "currency": "BRL",
      "status": "completed",
      "description": "Salário de junho",
      "created_at": "2026-06-28T14:30:00.000000Z"
    },
    {
      "id": "c3d4e5f6-a7b8-9012-cdef-123456789012",
      "type": "transfer",
      "amount": 200.00,
      "currency": "BRL",
      "status": "completed",
      "description": "Pagamento Maria",
      "created_at": "2026-06-28T15:00:00.000000Z"
    }
  ]
}
```

**Tipos de transação possíveis**

| type | Descrição |
|------|-----------|
| `deposit` | Depósito na carteira |
| `withdrawal` | Saque da carteira |
| `transfer` | Transferência enviada ou recebida |
| `reversal` | Estorno de uma transferência |

**Status possíveis**

| status | Descrição |
|--------|-----------|
| `pending` | Aguardando processamento |
| `completed` | Concluída com sucesso |
| `failed` | Falhou durante o processamento |
| `reversed` | Foi revertida (estornada) |

---

## 5. API — Transferência

---

### `POST /transactions/transfer` — Transferir para outro usuário

Transfere saldo da carteira do usuário autenticado para outro usuário. A operação é atômica — ou ambas as carteiras são atualizadas, ou nenhuma.

**Request**

```http
POST http://localhost:8000/api/v1/transactions/transfer
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "recipient_id": 2,
  "amount": 200.00,
  "description": "Pagamento Maria"
}
```

**Campos**

| Campo | Tipo | Obrigatório | Regras |
|-------|------|-------------|--------|
| recipient_id | integer | sim | ID de um usuário existente; não pode ser o próprio usuário |
| amount | number | sim | mínimo 0.01 |
| description | string | não | max 255 caracteres |

> O `recipient_id` é o ID numérico do usuário, não o UUID da wallet.

**Response 201 — Sucesso**

```json
{
  "success": true,
  "message": "Transferência realizada com sucesso",
  "data": {
    "id": "d4e5f6a7-b8c9-0123-defa-234567890123",
    "type": "transfer",
    "amount": 200.00,
    "currency": "BRL",
    "status": "completed",
    "description": "Pagamento Maria",
    "created_at": "2026-06-28T15:00:00.000000Z"
  }
}
```

**Response 422 — Transferência para a própria conta**

```json
{
  "message": "Você não pode transferir para a sua própria conta.",
  "errors": {
    "recipient_id": ["Você não pode transferir para a sua própria conta."]
  }
}
```

**Response 422 — Saldo insuficiente**

```json
{
  "success": false,
  "message": "Saldo insuficiente. Disponível: R$ 50,00, solicitado: R$ 200,00."
}
```

**Response 404 — Destinatário não encontrado**

```json
{
  "success": false,
  "message": "Usuário não encontrado."
}
```

---

## 6. API — Reversão

O fluxo de reversão tem três etapas: **solicitar**, **aprovar** e **rejeitar**. Apenas transferências concluídas podem ser revertidas.

---

### `POST /transactions/reversal/request` — Solicitar reversão

O usuário solicita o estorno de uma transferência. A solicitação fica com status `pending` até ser aprovada ou rejeitada.

**Quem pode solicitar:** o remetente ou o destinatário da transferência original.

**Request**

```http
POST http://localhost:8000/api/v1/transactions/reversal/request
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "transaction_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
  "reason": "user_request",
  "description": "Transferência feita para conta errada"
}
```

**Campos**

| Campo | Tipo | Obrigatório | Valores aceitos |
|-------|------|-------------|-----------------|
| transaction_id | string (UUID) | sim | UUID de uma transferência existente |
| reason | string | sim | `user_request`, `system_error`, `fraud_detection`, `compliance` |
| description | string | não | max 500 caracteres |

**Response 201 — Sucesso**

```json
{
  "success": true,
  "message": "Solicitação de reversão enviada com sucesso",
  "data": {
    "reversal_id": "e5f6a7b8-c9d0-1234-efab-345678901234",
    "original_transaction_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
    "status": "pending",
    "reason": "user_request"
  }
}
```

**Response 403 — Usuário não é dono da transação**

```json
{
  "success": false,
  "message": "Você só pode solicitar reversão para suas próprias transações"
}
```

**Response 422 — Transação não pode ser revertida**

```json
{
  "success": false,
  "message": "Apenas transferências concluídas podem ser revertidas."
}
```

---

### `POST /transactions/reversal/{id}/approve` — Aprovar reversão

Aprova a reversão e executa o estorno: o saldo é devolvido ao remetente original.

**Request**

```http
POST http://localhost:8000/api/v1/transactions/reversal/e5f6a7b8-c9d0-1234-efab-345678901234/approve
Authorization: Bearer {token}
```

> Nenhum body é necessário.

**Response 200 — Sucesso**

```json
{
  "success": true,
  "message": "Reversão aprovada e executada com sucesso",
  "data": {
    "reversal_id": "e5f6a7b8-c9d0-1234-efab-345678901234",
    "status": "completed",
    "approved_by": 1
  }
}
```

---

### `POST /transactions/reversal/{id}/reject` — Rejeitar reversão

Rejeita a solicitação de reversão. Os saldos não são alterados.

**Request**

```http
POST http://localhost:8000/api/v1/transactions/reversal/e5f6a7b8-c9d0-1234-efab-345678901234/reject
Authorization: Bearer {token}
```

> Nenhum body é necessário.

**Response 200 — Sucesso**

```json
{
  "success": true,
  "message": "Reversão rejeitada com sucesso",
  "data": {
    "reversal_id": "e5f6a7b8-c9d0-1234-efab-345678901234",
    "status": "rejected"
  }
}
```

---

### Máquina de estados da reversão

```
Solicitação criada
      │
      ▼
  [pending]
      │
      ├─── approve ──► [completed]   ← saldo revertido
      │
      └─── reject  ──► [rejected]    ← saldo inalterado
```

---

## 7. API — Extrato PDF

---

### `GET /transactions/{uuid}/receipt` — Download do comprovante

Baixa o comprovante da transação em formato PDF. Disponível para qualquer tipo de transação (depósito, saque, transferência, reversão).

**Request**

```http
GET http://localhost:8000/api/v1/transactions/a1b2c3d4-e5f6-7890-abcd-ef1234567890/receipt
Authorization: Bearer {token}
```

**Response 200 — Sucesso**

```
Content-Type: application/pdf
Content-Disposition: attachment; filename="extrato-a1b2c3d4-e5f6-7890-abcd-ef1234567890.pdf"
```

O arquivo PDF é retornado diretamente. Contém:
- Número do comprovante (UUID)
- Tipo da transação
- Valor e moeda
- Status
- Data e hora (fuso: America/Sao_Paulo)
- Partes envolvidas (remetente e/ou destinatário com nome e UUID da carteira)
- Dados de reversão (se aplicável)

**Response 403 — Sem permissão**

```json
{
  "success": false,
  "message": "Você não tem permissão para acessar este extrato."
}
```

**Response 404 — Transação não encontrada**

```json
{
  "message": "No query results for model [App\\Models\\Transaction]."
}
```

**Como baixar via curl**

```bash
curl -X GET http://localhost:8000/api/v1/transactions/{uuid}/receipt \
  -H "Authorization: Bearer {token}" \
  --output comprovante.pdf
```

---

## 8. Códigos de erro

| Código | Significado | Quando ocorre |
|--------|-------------|---------------|
| 200 | OK | Requisição bem-sucedida (leituras, logout, aprovações) |
| 201 | Created | Recurso criado com sucesso (register, deposit, transfer, etc.) |
| 400 | Bad Request | Erro genérico de processamento |
| 401 | Unauthorized | Token ausente, inválido ou expirado |
| 403 | Forbidden | Usuário autenticado mas sem permissão sobre o recurso |
| 404 | Not Found | Recurso (usuário, transação, reversão) não encontrado |
| 422 | Unprocessable Entity | Erro de validação ou regra de negócio violada |
| 500 | Internal Server Error | Erro interno (falha no banco, geração de PDF, etc.) |

### Formato padrão de erro de validação (422)

```json
{
  "message": "Descrição do erro.",
  "errors": {
    "campo": ["Mensagem de erro específica."]
  }
}
```

### Formato padrão de erro de negócio (400 / 403 / 404 / 422)

```json
{
  "success": false,
  "message": "Descrição do erro."
}
```

---

> Dúvidas ou problemas: nathan.ads.100@gmail.com
