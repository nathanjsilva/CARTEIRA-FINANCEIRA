# Carteira Financeira

Sistema de carteira digital desenvolvido com **Laravel 12** no backend e **Vue 3** no frontend. Permite criar contas, depositar, sacar, transferir entre usuários e solicitar reversões de transações, tudo com comprovante em PDF.

---

## Funcionalidades

- Cadastro e login de usuários
- Carteira digital em BRL criada automaticamente ao se cadastrar
- Depósito, saque e transferência entre usuários
- Histórico completo de transações com paginação
- Solicitação e aprovação de reversões/estornos
- Download de comprovante em PDF por transação
- Auditoria completa de todas as operações

---

## Stack Tecnológico

| Camada | Tecnologia | Versão |
|---|---|---|
| Backend | PHP + Laravel | 8.2 / 12 |
| Frontend | Vue 3 + Vite | 3.x |
| Banco de dados | MySQL | 8.0 |
| Cache e filas | Redis | Alpine |
| Servidor web | Nginx | Stable |
| Containerização | Docker | 24+ |

---

## Pré-requisitos

Você só precisa ter o **Docker Desktop** instalado na sua máquina.

- [Baixar Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Versão mínima recomendada: Docker Desktop 4.x

> Não é necessário instalar PHP, Node.js, MySQL ou qualquer outra dependência. Tudo roda dentro dos containers Docker.

---

## Instalação Passo a Passo

### 1. Clone o repositório

```bash
git clone <url-do-repositorio>
cd CARTEIRA-FINANCEIRA
```

### 2. Suba os containers

```bash
docker compose up -d --build
```

Esse comando vai baixar as imagens necessárias e criar todos os containers. Na primeira vez pode demorar alguns minutos dependendo da sua internet.

Você pode acompanhar o progresso com:

```bash
docker compose logs -f
```

### 3. Instale as dependências do PHP

```bash
docker exec carteira_api composer install
```

Aguarde a instalação completar. Você verá a mensagem `Generating optimized autoload files` ao final.

### 4. Corrija as permissões da pasta de storage

```bash
docker exec carteira_api chmod -R 777 storage bootstrap/cache
```

### 5. Rode as migrations e seeds

```bash
docker exec carteira_api php artisan migrate --seed
```

Isso vai criar todas as tabelas no banco de dados e popular com dados iniciais.

### 6. Acesse o sistema

Abra o navegador e acesse: **http://localhost:5173**

---

## URLs do Sistema

| Serviço | URL | Descrição |
|---|---|---|
| Frontend (app) | http://localhost:5173 | Interface principal do sistema |
| API (backend) | http://localhost:8000 | Endpoints da API REST |
| phpMyAdmin | http://localhost:8080 | Interface visual do banco de dados |

---

## Como Usar no Navegador

### Criando sua conta

1. Acesse **http://localhost:5173**
2. Clique em **"Criar conta"** ou vá para http://localhost:5173/register
3. Preencha os campos:
   - **Nome completo:** seu nome
   - **E-mail:** um e-mail válido (será usado para login)
   - **Senha:** mínimo 8 caracteres
   - **Confirmar senha:** repita a mesma senha
4. Clique no botão **"Cadastrar"**
5. Uma carteira em BRL é criada automaticamente para você
6. Você será redirecionado para o **Dashboard**

---

### Fazendo login

1. Vá para http://localhost:5173/login
2. Digite seu **e-mail** e **senha**
3. Clique em **"Entrar"**
4. Você será redirecionado para o Dashboard

---

### Dashboard (Página Principal)

O Dashboard é a página central do sistema. Nela você encontra:

**Saldo atual**
- Exibe o saldo da sua carteira em reais (R$)
- Atualizado automaticamente após cada operação

**Botão "Depositar"**
- Abre um formulário para adicionar dinheiro à sua carteira
- Preencha o **valor** (mínimo R$ 0,01) e uma **descrição** opcional
- Clique em **"Confirmar"** para realizar o depósito
- O saldo é atualizado imediatamente

**Botão "Sacar"**
- Abre um formulário para retirar dinheiro da sua carteira
- Preencha o **valor** desejado e uma **descrição** opcional
- Se o saldo for insuficiente, o sistema exibirá um aviso
- Clique em **"Confirmar"** para realizar o saque

**Botão "Transferir"**
- Abre um formulário para enviar dinheiro para outro usuário
- Preencha o **ID do destinatário** (o ID numérico do usuário), o **valor** e uma **descrição** opcional
- Não é possível transferir para a própria conta
- Clique em **"Confirmar"** para realizar a transferência

**Últimas transações**
- Exibe as 5 transações mais recentes
- Mostra o tipo (depósito, saque, transferência), valor e status
- Clique em **"Ver todas"** para ir ao histórico completo

**Botão de logout**
- Localizado no canto superior da tela
- Encerra sua sessão e invalida o token de acesso

---

### Histórico de Transações

Acesse clicando em **"Ver todas"** no dashboard ou indo para http://localhost:5173/transactions

**O que você encontra nessa página:**

**Lista completa de transações**
- Todas as suas movimentações em ordem cronológica
- Cada linha mostra: data, tipo, valor, descrição e status

**Campo de busca**
- Digite qualquer texto para filtrar transações por descrição ou valor

**Filtro por tipo**
- Filtre para ver apenas: Depósitos, Saques, Transferências ou Reversões

**Botão "PDF" em cada transação**
- Faz o download do comprovante daquela transação em formato PDF
- O arquivo é gerado na hora e baixado automaticamente

**Botão "Solicitar Reversão"**
- Disponível em transações elegíveis (depósitos, saques, transferências)
- Abre um formulário para justificar o pedido de estorno
- Escolha o motivo:
  - **Solicitação do usuário** (`user_request`) — você quer cancelar a transação
  - **Erro do sistema** (`system_error`) — houve algum problema técnico
  - **Detecção de fraude** (`fraud_detection`) — a transação foi feita de forma indevida
  - **Conformidade** (`compliance`) — questão regulatória
- Adicione uma descrição opcional e confirme
- A reversão fica com status **"Pendente"** até ser aprovada manualmente ou pelo job automático

---

## Erros Comuns e Soluções

### `vendor/autoload.php: No such file or directory`

**Causa:** As dependências PHP não foram instaladas.

**Solução:**
```bash
docker exec carteira_api composer install
```

---

### `Permission denied` no storage

**Causa:** A pasta `storage/` não tem permissão de escrita.

**Solução:**
```bash
docker exec carteira_api chmod -R 777 storage bootstrap/cache
```

---

### `Class "Redis" not found`

**Causa:** O `.env` está configurado com `REDIS_CLIENT=phpredis`, mas essa extensão não está instalada. O projeto usa `predis`.

**Solução:** Verifique se o arquivo `backend/.env` contém:
```
REDIS_CLIENT=predis
```
Se não estiver correto, edite o arquivo e depois reinicie:
```bash
docker compose restart app
```

---

### `Table 'carteira_financeira.users' doesn't exist`

**Causa:** As migrations não foram rodadas ainda.

**Solução:**
```bash
docker exec carteira_api php artisan migrate --seed
```

---

### `CORS error` no navegador

**Causa:** O backend ainda está inicializando ou há algum erro no container da API.

**Solução:** Verifique os logs e aguarde:
```bash
docker compose logs app --tail=20
```

---

### `502 Bad Gateway`

**Causa:** O PHP-FPM não está respondendo — geralmente o container `app` ainda está iniciando ou falhou.

**Solução:**
```bash
# Verifique o status
docker compose ps

# Veja os logs do app
docker compose logs app --tail=30
```

---

### Nada funciona / banco corrompido

Pare tudo, remova os volumes e recomece do zero:

```bash
docker compose down -v
docker compose up -d --build
docker exec carteira_api composer install
docker exec carteira_api chmod -R 777 storage bootstrap/cache
docker exec carteira_api php artisan migrate --seed
```

---

## Comandos Úteis

### Ver logs em tempo real

```bash
# Todos os containers
docker compose logs -f

# Apenas o backend (API)
docker compose logs -f app

# Apenas o frontend
docker compose logs -f frontend

# Apenas o Nginx
docker compose logs -f nginx
```

---

### Ver logs do Laravel

```bash
# Ver o log completo
docker exec carteira_api cat storage/logs/laravel.log

# Ver apenas as últimas 50 linhas
docker exec carteira_api tail -50 storage/logs/laravel.log

# Limpar o log
docker exec carteira_api sh -c "> storage/logs/laravel.log"
```

---

### Banco de dados

```bash
# Rodar migrations
docker exec carteira_api php artisan migrate

# Rodar migrations com seeds (popula dados iniciais)
docker exec carteira_api php artisan migrate --seed

# Resetar o banco completamente (apaga tudo e recria)
docker exec carteira_api php artisan migrate:fresh --seed

# Acessar o MySQL diretamente pelo terminal
docker exec -it carteira_db mysql -u nathan_carteira -p1234 carteira_financeira
```

---

### Limpar cache

```bash
# Limpar cache da aplicação
docker exec carteira_api php artisan cache:clear

# Limpar cache de configuração
docker exec carteira_api php artisan config:clear

# Limpar cache de rotas
docker exec carteira_api php artisan route:clear

# Limpar tudo de uma vez
docker exec carteira_api php artisan optimize:clear
```

---

### Gerenciar containers

```bash
# Ver status de todos os containers
docker compose ps

# Parar os containers (sem apagar dados)
docker compose stop

# Parar e remover os containers (dados do banco são mantidos)
docker compose down

# Parar, remover containers E volumes (apaga o banco de dados)
docker compose down -v

# Reiniciar um container específico
docker compose restart app

# Acessar o terminal do container da API
docker exec -it carteira_api sh
```

---

### Rodar testes

```bash
docker exec carteira_api php artisan test
```

---

## Testando com Postman

O projeto inclui uma coleção Postman pronta para uso.

### Importando no Postman

1. Abra o Postman
2. Clique em **"Import"** (canto superior esquerdo)
3. Selecione o arquivo `backend/postman_collection.json`
4. Também importe o arquivo `backend/postman_environment.json`
5. No canto superior direito, selecione o environment **"Carteira Financeira - Local"**

### Fluxo básico de teste

1. Execute **"Registrar Usuário"** para criar uma conta
2. Execute **"Login"** — o token é salvo automaticamente na variável `{{token}}`
3. Execute **"Consultar Saldo"** para verificar a carteira
4. Execute **"Depósito"** para adicionar saldo
5. Para transferir, crie um segundo usuário e use o ID dele no campo `recipient_id`
6. No **"Histórico de Transações"**, copie o `uuid` de uma transação
7. Use esse `uuid` para baixar o comprovante PDF ou solicitar uma reversão

> Para mais detalhes sobre cada endpoint, consulte `backend/POSTMAN_GUIDE.md`.

---

## Estrutura dos Containers

| Container | Nome | Função |
|---|---|---|
| API | `carteira_api` | Aplicação Laravel (PHP-FPM) |
| Nginx | `carteira_nginx` | Servidor web — porta 8000 |
| Scheduler | `carteira_scheduler` | Jobs agendados do Laravel |
| Queue | `carteira_queue` | Processamento de filas Redis |
| Frontend | `carteira_frontend` | Vue 3 + Vite — porta 5173 |
| MySQL | `carteira_db` | Banco de dados — porta 3306 |
| Redis | `carteira_redis` | Cache e filas — porta 6379 |
| phpMyAdmin | `carteira_phpmyadmin` | Interface do banco — porta 8080 |
