# Guia de Uso do Postman - API Carteira Financeira

## 📋 Configuração Inicial

### 1. Importar Collection e Environment

1. Abra o Postman
2. Clique em **Import**
3. Importe os arquivos:
   - `postman_collection.json` (Collection)
   - `postman_environment.json` (Environment)
4. Selecione o environment **"Carteira Financeira - Local"**

### 2. Configurar Environment

Certifique-se de que o environment está ativo e configurado com:
- `base_url`: `http://localhost:8000`

## 🔐 Fluxo de Autenticação

### Passo 1: Registrar Usuário
1. Execute a requisição **"Registrar Usuário"**
2. Use os dados de exemplo ou crie seus próprios:
```json
{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Passo 2: Fazer Login
1. Execute a requisição **"Login"**
2. Use o email e senha do usuário criado:
```json
{
    "email": "joao@example.com",
    "password": "password123"
}
```

**🎯 IMPORTANTE:** O token será automaticamente salvo no environment após o login bem-sucedido!

### Passo 3: Usar o Token
Todas as requisições protegidas usarão automaticamente o token salvo no header `Authorization: Bearer {{token}}`

## 💰 Testando Operações da Carteira

### 1. Consultar Saldo
- Execute **"Consultar Saldo"**
- Retorna o saldo atual da carteira

### 2. Fazer Depósito
- Execute **"Depósito"**
- Exemplo de body:
```json
{
    "amount": 100.50,
    "description": "Depósito inicial"
}
```

### 3. Fazer Saque
- Execute **"Saque"**
- Exemplo de body:
```json
{
    "amount": 50.00,
    "description": "Saque para pagamento"
}
```

### 4. Transferir para Outro Usuário
- Execute **"Transferência"**
- **IMPORTANTE**: Use `to_wallet_id` (não `to_user_id`)
- Exemplo de body:
```json
{
    "to_wallet_id": 2,
    "amount": 25.75,
    "description": "Transferência para amigo"
}
```

### 5. Ver Histórico
- Execute **"Histórico de Transações"**
- Use parâmetros de query para paginação:
  - `page`: número da página
  - `per_page`: itens por página

## 🔄 Testando Reversões de Transações

### 1. Solicitar Reversão
- Execute **"Solicitar Reversão"**
- **IMPORTANTE**: Use os valores corretos para `reason`:
  - `user_request`
  - `system_error`
  - `fraud_detection`
  - `compliance`
- Exemplo de body:
```json
{
    "transaction_id": "uuid-da-transacao",
    "reason": "user_request",
    "description": "Solicitação do usuário"
}
```

**🎯 IMPORTANTE:** O ID da reversão será automaticamente salvo no environment!

### 2. Aprovar/Rejeitar Reversão
- Use **"Aprovar Reversão"** ou **"Rejeitar Reversão"**
- O `reversal_id` será usado automaticamente das variáveis do environment

### 3. Consultar Reversões
- **"Reversões Pendentes"**: Lista reversões aguardando aprovação
- **"Histórico de Reversões"**: Lista todas as reversões com paginação

## 🚀 Fluxo de Teste Completo

### Cenário: Usuário Completo
1. **Registrar Usuário** → Criar conta
2. **Login** → Obter token (salvo automaticamente)
3. **Consultar Saldo** → Verificar saldo inicial (R$ 0,00)
4. **Depósito** → Adicionar R$ 100,00
5. **Consultar Saldo** → Verificar novo saldo (R$ 100,00)
6. **Saque** → Retirar R$ 30,00
7. **Consultar Saldo** → Verificar saldo final (R$ 70,00)
8. **Histórico de Transações** → Ver todas as operações

### Cenário: Transferência entre Usuários
1. **Registrar Usuário** → Criar segundo usuário
2. **Login** → Fazer login com segundo usuário
3. **Depósito** → Adicionar saldo ao segundo usuário
4. **Transferência** → Transferir do primeiro para o segundo
5. **Consultar Saldo** → Verificar saldos atualizados

### Cenário: Reversão de Transação
1. **Fazer uma transferência** → Criar transação para reverter
2. **Solicitar Reversão** → Solicitar reversão da transação
3. **Reversões Pendentes** → Ver solicitação pendente
4. **Aprovar Reversão** → Aprovar a reversão
5. **Consultar Saldo** → Verificar que o valor foi revertido

## 🔧 Variáveis Automáticas

A collection está configurada para salvar automaticamente:

- **`token`**: Token de autenticação (após login)
- **`user_id`**: ID do usuário logado
- **`reversal_id`**: ID da reversão criada

## 📊 Códigos de Resposta

- **200**: Sucesso
- **201**: Criado com sucesso
- **400**: Erro de validação
- **401**: Não autenticado
- **403**: Não autorizado
- **404**: Não encontrado
- **422**: Erro de validação de dados
- **500**: Erro interno do servidor

## 🐛 Troubleshooting

### Token Expirado
Se receber erro 401, faça login novamente para obter um novo token.

### Rota Não Encontrada
Verifique se o servidor está rodando em `http://localhost:8000`

### Erro de Validação
- **Transferência**: Use `to_wallet_id` (não `to_user_id`)
- **Reversão**: Use valores corretos para `reason` (`user_request`, `system_error`, `fraud_detection`, `compliance`)
- Verifique se todos os campos obrigatórios estão preenchidos corretamente

### Problemas de Conexão
Certifique-se de que os containers Docker estão rodando:
```bash
docker-compose ps
```

### Erro de Migração
Se houver problemas com o banco de dados:
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

## 📝 Notas Importantes

1. **Sempre faça login primeiro** para obter o token
2. **O token é salvo automaticamente** após o login
3. **Use as variáveis do environment** para IDs dinâmicos
4. **Teste o fluxo completo** para validar todas as funcionalidades
5. **Verifique os logs** no terminal se houver problemas

## 🎯 Funcionalidades Implementadas

✅ **Sistema Completo de Carteira Financeira**
- Autenticação com Laravel Sanctum
- Operações de depósito, saque e transferência
- Sistema de reversão de transações
- Validação rigorosa de dados
- Logs de auditoria completos
- Testes automatizados com PHPUnit
- Observers para automatização
- Documentação completa

## 🚀 Tecnologias Utilizadas

- **Laravel 12** com PHP 8.2
- **MySQL 8.0** para persistência
- **Redis** para cache e sessões
- **Docker** para containerização
- **PHPUnit** para testes
- **Laravel Sanctum** para autenticação
- **Spatie Activity Log** para auditoria

