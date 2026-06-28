# Guia Postman — API Carteira Financeira

## Configuração Inicial

### 1. Importar a coleção

1. Abra o Postman
2. Clique em **Import** (canto superior esquerdo)
3. Importe os dois arquivos:
   - `backend/postman_collection.json` — todos os endpoints
   - `backend/postman_environment.json` — variáveis de ambiente
4. No canto superior direito, selecione o environment **"Carteira Financeira - Local"**

### 2. Variáveis disponíveis

| Variável | Descrição | Preenchida por |
|---|---|---|
| `base_url` | URL base da API (`http://localhost:8000`) | Já configurada |
| `token` | Token de autenticação Bearer | Salvo automaticamente no Login |
| `user_id` | ID do usuário logado | Salvo automaticamente no Login |
| `reversal_id` | ID da reversão criada | Salvo automaticamente ao solicitar reversão |
| `transaction_uuid` | UUID de uma transação | Você copia do histórico manualmente |

---

## Endpoints Disponíveis

### Autenticação

#### Registrar Usuário
- **Método:** `POST`
- **URL:** `/api/v1/auth/register`
- **Body:**
```json
{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Login
- **Método:** `POST`
- **URL:** `/api/v1/auth/login`
- **Body:**
```json
{
    "email": "joao@example.com",
    "password": "password123"
}
```
> O token é salvo automaticamente na variável `{{token}}` via script do Postman.

#### Logout
- **Método:** `POST`
- **URL:** `/api/v1/auth/logout`
- **Header:** `Authorization: Bearer {{token}}`

#### Meu Perfil
- **Método:** `GET`
- **URL:** `/api/v1/auth/me`
- **Header:** `Authorization: Bearer {{token}}`

---

### Carteira

#### Consultar Saldo
- **Método:** `GET`
- **URL:** `/api/v1/wallet/balance`

#### Depósito
- **Método:** `POST`
- **URL:** `/api/v1/wallet/deposit`
- **Body:**
```json
{
    "amount": 100.50,
    "description": "Depósito inicial"
}
```

#### Saque
- **Método:** `POST`
- **URL:** `/api/v1/wallet/withdraw`
- **Body:**
```json
{
    "amount": 50.00,
    "description": "Saque para pagamento"
}
```
> Retorna erro 422 se o saldo for insuficiente.

#### Histórico de Transações
- **Método:** `GET`
- **URL:** `/api/v1/wallet/history?page=1&per_page=10`

---

### Transferências

#### Transferência entre usuários
- **Método:** `POST`
- **URL:** `/api/v1/transactions/transfer`
- **Body:**
```json
{
    "recipient_id": 2,
    "amount": 25.75,
    "description": "Transferência para amigo"
}
```
> `recipient_id` é o ID numérico do usuário destinatário. Não é possível transferir para si mesmo.

---

### Reversões

#### Solicitar Reversão
- **Método:** `POST`
- **URL:** `/api/v1/transactions/reversal/request`
- **Body:**
```json
{
    "transaction_id": "UUID-DA-TRANSACAO-AQUI",
    "reason": "user_request",
    "description": "Solicitação de estorno"
}
```

> **`transaction_id`** deve ser o **UUID** da transação (formato `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`), obtido no histórico.

> **`reason`** aceita apenas um destes valores:
> - `user_request` — solicitação do usuário
> - `system_error` — erro do sistema
> - `fraud_detection` — detecção de fraude
> - `compliance` — conformidade regulatória

#### Aprovar Reversão
- **Método:** `POST`
- **URL:** `/api/v1/transactions/reversal/{{reversal_id}}/approve`
- **Body:**
```json
{
    "description": "Reversão aprovada após análise"
}
```

#### Rejeitar Reversão
- **Método:** `POST`
- **URL:** `/api/v1/transactions/reversal/{{reversal_id}}/reject`
- **Body:**
```json
{
    "description": "Reversão rejeitada - transação válida"
}
```

---

### Comprovantes

#### Download Comprovante PDF
- **Método:** `GET`
- **URL:** `/api/v1/transactions/{{transaction_uuid}}/receipt`
- **Header:** `Accept: application/pdf`

> No Postman, após executar a requisição clique em **"Save to a file"** para salvar o PDF gerado.

---

### Sistema

#### Health Check
- **Método:** `GET`
- **URL:** `/api/health`
- Não requer autenticação. Retorna `{"status": "ok", "version": "v1"}`.

---

## Fluxo Completo de Teste

### Fluxo básico

1. **Registrar** um usuário
2. **Login** — token salvo automaticamente
3. **Depositar** um valor
4. **Consultar Saldo** — confirmar depósito
5. **Histórico** — ver a transação criada

### Fluxo de transferência

1. Registrar **usuário A** e fazer login
2. Registrar **usuário B** (anote o ID retornado)
3. Depositar na conta do usuário A
4. Fazer login novamente como usuário A
5. Transferir para o usuário B usando o `recipient_id` do usuário B

### Fluxo de reversão

1. Fazer uma transação (depósito, saque ou transferência)
2. No **Histórico**, copiar o `uuid` da transação
3. Executar **Solicitar Reversão** com o UUID e o motivo
4. O `reversal_id` é salvo automaticamente
5. Executar **Aprovar Reversão** ou **Rejeitar Reversão**

---

## Erros Comuns

| Status | Causa | Solução |
|---|---|---|
| `401 Unauthorized` | Token expirado ou ausente | Execute Login novamente |
| `404 Not Found` | Rota incorreta | Verifique se a URL contém `/api/v1/` |
| `422 Unprocessable Entity` | Dados inválidos | Leia o campo `errors` na resposta |
| `403 Forbidden` | Sem permissão | Você não pode acessar o recurso de outro usuário |
| `500 Internal Server Error` | Erro no servidor | Verifique os logs: `docker compose logs app` |
