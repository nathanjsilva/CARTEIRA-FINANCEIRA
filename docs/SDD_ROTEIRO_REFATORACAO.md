# SDD: Roteiro de Refatoração

> Referência cruzada: [[SDD_CONTEXTO_GERAL]] | [[SDD_ARQUITETURA_ALVO]]

**Premissa:** A arquitetura DDD já está parcialmente implementada. Este roteiro elimina as inconsistências híbridas e fecha os gaps identificados, sem reescrever o que já funciona corretamente.

**Critério de conclusão de cada fase:** todos os testes da fase passam e nenhum teste existente quebra.

---

## Fase 1: Corrigir Value Object Money (Semana 1)

**Por quê primeiro:** `float` em cálculos financeiros é um risco técnico que afeta toda a base do Domain. Resolver isso primeiro garante que todas as fases seguintes constroem sobre uma fundação sólida.

**Dependências:** nenhuma

### Tarefas

- [ ] Migrar `Money` de `float` para `int` (centavos) em `app/Domain/ValueObjects/Money.php`
- [ ] Adicionar `Money::fromFloat(float): self` e `Money::getCents(): int`
- [ ] Atualizar `User::deposit()`, `User::withdraw()`, `User::transfer()` — sem mudança de assinatura pública
- [ ] Atualizar `EloquentUserRepository::toDomain()` — usar `Money::fromFloat((float) $wallet->balance)`
- [ ] Atualizar `EloquentTransactionRepository::save()` — usar `$transaction->getAmount()->toFloat()`
- [ ] Atualizar `TransactionResponseDTO` — campo `amount` continua em float na saída da API
- [ ] Adicionar `TransactionStatus` ValueObject em `app/Domain/ValueObjects/TransactionStatus.php`
- [ ] Mover lógica de transições válidas do `TransactionObserver` para `TransactionStatus`
- [ ] Atualizar `Transaction::complete()`, `reverse()`, `fail()` para usar `TransactionStatus`

### Código de referência

```php
// app/Domain/ValueObjects/Money.php (após migração)
final class Money
{
    private function __construct(private readonly int $cents) {}

    public static function of(int $cents): self
    {
        if ($cents < 0) throw new \DomainException('Money cannot be negative');
        return new self($cents);
    }

    public static function fromFloat(float $amount): self
    {
        return self::of((int) round($amount * 100));
    }

    public static function zero(): self { return new self(0); }

    public function add(Money $other): self   { return new self($this->cents + $other->cents); }
    public function subtract(Money $other): self
    {
        $result = $this->cents - $other->cents;
        if ($result < 0) throw new \DomainException('Money cannot be negative');
        return new self($result);
    }

    public function isGreaterOrEqual(Money $other): bool { return $this->cents >= $other->cents; }
    public function getCents(): int  { return $this->cents; }
    public function toFloat(): float { return $this->cents / 100; }
    public function getAmount(): float { return $this->toFloat(); } // retrocompat
    public function format(): string
    {
        return 'R$ ' . number_format($this->toFloat(), 2, ',', '.');
    }
}
```

### Validação da Fase 1

```bash
php artisan test tests/Unit/Domain/ValueObjects/MoneyTest.php
# Deve passar todos + novos testes de precisão:
# ✅ soma 0.1 + 0.2 = 0.3 exato
# ✅ não permite Money negativo
# ✅ fromFloat(10.99) = 1099 cents
```

---

## Fase 2: Application Services de Auth (Semana 1-2)

**Por quê:** `AuthController` usa Eloquent diretamente, violando DDD. Registro e login devem passar pelo Domain.

**Dependência:** Fase 1 concluída

### Tarefas

- [ ] Criar `app/Application/DTOs/Auth/RegisterRequestDTO.php`
- [ ] Criar `app/Application/DTOs/Auth/AuthResponseDTO.php`
- [ ] Criar `app/Application/Services/Auth/RegisterUserService.php`
- [ ] Criar `app/Application/Services/Auth/LoginService.php`
- [ ] Adicionar `UserRepository::create(User $user): void`  na interface
- [ ] Implementar `EloquentUserRepository::create()` — `User::create([...])` + `createDefaultWallet()`
- [ ] Adicionar `Email::of(string): self` com validação de formato
- [ ] Atualizar `AuthController::register()` para usar `RegisterUserService`
- [ ] Atualizar `AuthController::login()` para usar `LoginService`
- [ ] Remover dependência de `Hash`, `Auth::attempt()` do Controller

### Código de referência

```php
// app/Application/Services/Auth/RegisterUserService.php
final class RegisterUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function execute(RegisterRequestDTO $dto): AuthResponseDTO
    {
        $user = User::register(
            id:             (string) \Illuminate\Support\Str::uuid(),
            name:           $dto->name,
            email:          $dto->email,
            hashedPassword: \Illuminate\Support\Facades\Hash::make($dto->password),
        );

        $this->userRepository->create($user);  // persiste user + cria wallet via Observer

        return new AuthResponseDTO(
            userId:    $user->getId(),
            name:      $user->getName(),
            email:     $user->getEmail(),
            balance:   0.0,
        );
    }
}

// app/Domain/Repositories/UserRepository.php (adicionar)
interface UserRepository
{
    public function findById(string $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
    public function create(User $user): void;  // ← novo
}
```

### Validação da Fase 2

```bash
php artisan test tests/Feature/Api/AuthControllerTest.php
# ✅ registro cria user + wallet
# ✅ login retorna token válido
# ✅ me retorna dados corretos
```

---

## Fase 3: Mover TransactionReversalService para Application (Semana 2)

**Por quê:** `app/Services/TransactionReversalService.php` usa Eloquent diretamente e mora fora das camadas DDD. O Controller de Transação também acessa Eloquent diretamente para buscas.

**Dependência:** Fase 2 concluída

### Tarefas

- [ ] Mover `app/Services/TransactionReversalService.php` → `app/Application/Services/Transaction/TransactionReversalService.php`
- [ ] Criar `app/Application/DTOs/Transaction/ReversalRequestDTO.php` (já existe, mover para subpasta)
- [ ] Adicionar `TransactionRepository::findByUuid(string $uuid): ?Transaction` na interface
- [ ] Implementar `EloquentTransactionRepository::findByUuid()`
- [ ] Refatorar `TransactionController::requestReversal()` para usar o repository (não Eloquent direto)
- [ ] Criar `app/Domain/Exceptions/WalletNotFoundException.php`
- [ ] Criar `app/Domain/Exceptions/InvalidTransactionException.php`
- [ ] Substituir `throw new \Exception(...)` em `TransactionReversalService` pelas exceptions de domínio
- [ ] Atualizar bind no `AppServiceProvider` se namespace mudou

### Código de referência

```php
// app/Infrastructure/Http/Controllers/Api/V1/TransactionController.php
public function requestReversal(ReversalRequest $request): JsonResponse
{
    $transaction = $this->transactionRepository->findByUuid($request->transaction_id);

    if (!$transaction) {
        return response()->json(['success' => false, 'message' => 'Transação não encontrada'], 404);
    }

    // Verifica ownership via domain — não via Eloquent direto
    $userId = (string) $request->user()->id;
    if (!$transaction->belongsToUser($userId)) {
        return response()->json(['success' => false, 'message' => 'Acesso negado'], 403);
    }

    $reversal = $this->reversalService->execute(
        new ReversalRequestDTO(
            transactionId: $transaction->getId(),
            requestedBy:   $userId,
            reason:        $request->reason,
            description:   $request->description,
        )
    );

    return response()->json(['success' => true, 'data' => $reversal->toArray()], 201);
}
```

### Validação da Fase 3

```bash
php artisan test tests/Feature/Api/TransactionControllerTest.php
# ✅ requestReversal retorna 201 para transação própria
# ✅ requestReversal retorna 403 para transação de outro user
# ✅ approveReversal executa reversão e atualiza saldos
# ✅ rejectReversal marca como rejected
```

---

## Fase 4: Presentation Layer (FormRequests + Resources) (Semana 3)

**Por quê:** Validação inline nos Controllers é duplicação e dificulta reutilização. API Resources garantem formato consistente.

**Dependência:** Fase 3 concluída

### Tarefas

- [ ] Criar `app/Presentation/Http/Requests/RegisterRequest.php`
- [ ] Criar `app/Presentation/Http/Requests/LoginRequest.php`
- [ ] Criar `app/Presentation/Http/Requests/DepositRequest.php`
- [ ] Criar `app/Presentation/Http/Requests/WithdrawRequest.php`
- [ ] Criar `app/Presentation/Http/Requests/TransferRequest.php`
- [ ] Criar `app/Presentation/Http/Requests/ReversalRequest.php`
- [ ] Criar `app/Presentation/Http/Resources/UserResource.php`
- [ ] Criar `app/Presentation/Http/Resources/WalletResource.php`
- [ ] Criar `app/Presentation/Http/Resources/TransactionResource.php`
- [ ] Atualizar Controllers para usar FormRequests (remover `$request->validate([...])` inline)
- [ ] Atualizar Controllers para retornar Resources em vez de arrays manuais
- [ ] Corrigir `WalletController::balance()` — expor `uuid` em vez de `id`
- [ ] Remover `app/Http/Controllers/Api/` (controllers legados)

### Código de referência

```php
// app/Presentation/Http/Requests/TransferRequest.php
class TransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'exists:users,id'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'description'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_id.exists' => 'Destinatário não encontrado.',
            'amount.min'          => 'O valor mínimo é R$ 0,01.',
        ];
    }
}

// app/Presentation/Http/Resources/TransactionResource.php
class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->uuid,
            'type'        => $this->type,
            'amount'      => (float) $this->amount,
            'currency'    => $this->currency,
            'status'      => $this->status,
            'description' => $this->description,
            'created_at'  => $this->created_at->toISOString(),
            'processed_at'=> $this->processed_at?->toISOString(),
        ];
    }
}
```

### Validação da Fase 4

```bash
# Verificar que controllers legados foram removidos
ls backend/app/Http/Controllers/Api/  # deve estar vazio ou não existir

# Testar formato de resposta
php artisan test tests/Feature/Api/

# Verificar que uuid é exposto, não id
curl -s http://localhost:8000/api/v1/wallet/balance | jq '.data.wallet_id'
# Deve retornar UUID, não integer
```

---

## Fase 5: Remover Rotas Legadas e Rate Limiting (Semana 3)

**Por quê:** Rotas duplicadas em `api.php` criam ambiguidade. Rate limiting é requisito de segurança para endpoints financeiros.

**Dependência:** Fase 4 concluída

### Tarefas

- [ ] Remover bloco de rotas legadas do `routes/api.php` (linhas 47-72)
- [ ] Verificar que frontend Vue usa `/api/v1/...` (já usa por `VITE_API_BASE_URL`)
- [ ] Adicionar rate limiting no `AppServiceProvider` ou `bootstrap/app.php`:
  - Auth endpoints: 10 req/min por IP
  - Transfer: 30 req/min por usuário
  - Deposit/Withdraw: 60 req/min por usuário
- [ ] Adicionar middleware de rate limit nas rotas
- [ ] Adicionar validação de ownership em `approveReversal`/`rejectReversal` (atualmente qualquer user pode)

### Código de referência

```php
// routes/api.php (limpo, sem legado)
Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => 'v1']));

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
        });

        Route::prefix('wallet')->middleware('throttle:60,1')->group(function () {
            Route::get('balance',   [WalletController::class, 'balance']);
            Route::post('deposit',  [WalletController::class, 'deposit']);
            Route::post('withdraw', [WalletController::class, 'withdraw']);
            Route::get('history',   [WalletController::class, 'history']);
        });

        Route::prefix('transactions')->middleware('throttle:30,1')->group(function () {
            Route::post('transfer',                        [TransactionController::class, 'transfer']);
            Route::post('reversal/request',               [TransactionController::class, 'requestReversal']);
            Route::post('reversal/{reversalId}/approve',  [TransactionController::class, 'approveReversal']);
            Route::post('reversal/{reversalId}/reject',   [TransactionController::class, 'rejectReversal']);
        });
    });
});
```

### Validação da Fase 5

```bash
# Rotas legadas devem retornar 404
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/auth/login
# Esperado: 404

# Rate limit deve funcionar
for i in {1..12}; do curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8000/api/v1/auth/login; done
# Esperado: 200 (ou 401) para primeiros 10, 429 a partir do 11
```

---

## Fase 6: Cache de Saldo + Events (Semana 4)

**Por quê:** Balance é lida frequentemente mas muda pouco. Events desacoplam side-effects (notificações, auditoria) do fluxo principal.

**Dependência:** Fase 5 concluída

### Tarefas

- [ ] Criar `app/Infrastructure/Cache/RedisWalletBalanceCache.php`
- [ ] Integrar cache em `WalletController::balance()` via decorator ou direto no Service
- [ ] Invalidar cache nos Services de Deposit, Withdraw, Transfer (após DB::transaction)
- [ ] Criar `app/Application/Events/TransactionCompleted.php`
- [ ] Criar `app/Application/Events/HighValueTransactionDetected.php`
- [ ] Mover lógica de "alto valor" do `TransactionObserver` para listener de evento
- [ ] Registrar listeners no `AppServiceProvider` ou `EventServiceProvider`
- [ ] Garantir que Observers só fazem logging — sem lógica de negócio

### Código de referência

```php
// app/Infrastructure/Cache/RedisWalletBalanceCache.php
final class RedisWalletBalanceCache
{
    private const TTL = 60; // segundos

    public function __construct(private readonly \Illuminate\Contracts\Cache\Repository $cache) {}

    public function get(string $userId): ?float
    {
        return $this->cache->get("wallet:balance:{$userId}");
    }

    public function set(string $userId, float $balance): void
    {
        $this->cache->put("wallet:balance:{$userId}", $balance, self::TTL);
    }

    public function invalidate(string $userId): void
    {
        $this->cache->forget("wallet:balance:{$userId}");
    }
}

// app/Application/Events/TransactionCompleted.php
final class TransactionCompleted
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $senderId,
        public readonly string $recipientId,
        public readonly float  $amount,
        public readonly string $type,
    ) {}
}
```

### Validação da Fase 6

```bash
# Cache deve funcionar
redis-cli KEYS "wallet:balance:*"  # deve aparecer após GET /balance

# Evento deve disparar
php artisan test tests/Feature/Api/IntegrationTest.php
```

---

## Fase 7: Testes, Documentação e Docker Prod (Semana 4-5)

**Por quê:** Cobertura de testes e ambiente de produção são necessários para o projeto ser considerado "entregável".

**Dependência:** Todas as fases anteriores

### Tarefas

- [ ] Cobrir `RegisterUserService` e `LoginService` com testes Unit (mock `UserRepository`)
- [ ] Cobrir `TransactionReversalService` com testes Unit
- [ ] Adicionar teste de integração: fluxo completo register → deposit → transfer → reversal
- [ ] Adicionar teste para `Money` (precisão float→int)
- [ ] Adicionar teste para `TransactionStatus` (transições inválidas)
- [ ] Criar `backend/Dockerfile` otimizado para produção (sem devDependencies)
- [ ] Adicionar `.env.production.example` com variáveis necessárias
- [ ] Verificar que secrets não estão hardcoded em `docker-compose.yml`
- [ ] Adicionar `Email` ValueObject no construtor de `User::register()`

### Validação Final

```bash
# Todos os testes passam
php artisan test
# Expected: Tests: XX passed

# Sem controllers legados
ls backend/app/Http/Controllers/Api/
# Expected: directory empty or not found

# Cobertura de testes
php artisan test --coverage --min=80

# Docker sobe limpo
docker-compose up -d
curl http://localhost:8000/api/health
# Expected: {"status":"ok","version":"v1"}
```

---

## Dependências entre Fases

```
Fase 1 (Money VO)
    └─► Fase 2 (Auth Services)
            └─► Fase 3 (Reversal Service)
                    └─► Fase 4 (Presentation Layer)
                            └─► Fase 5 (Routes + Rate Limit)
                                    └─► Fase 6 (Cache + Events)
                                            └─► Fase 7 (Testes + Docker)
```

---

## Checklist de Entrega Final

```
Domain
  ✅ Money usa int (centavos)
  ✅ TransactionStatus encapsula transições válidas
  ✅ Email ValueObject usado em User
  ✅ Entities sem dependência de Laravel/Eloquent

Application
  ✅ RegisterUserService e LoginService existem
  ✅ TransactionReversalService em Application/Services/Transaction/
  ✅ DTOs organizados em subpastas Auth, Wallet, Transaction

Infrastructure
  ✅ EloquentUserRepository::create() implementado
  ✅ EloquentTransactionRepository::findByUuid() implementado
  ✅ Redis cache de saldo implementado
  ✅ Observers apenas log/eventos (zero lógica de negócio)

Presentation
  ✅ FormRequests para todos os endpoints
  ✅ API Resources para todas as respostas
  ✅ Controllers legados removidos de app/Http/Controllers/Api/
  ✅ uuid exposto (nunca id interno)

Routes
  ✅ Rotas legadas removidas de api.php
  ✅ Rate limiting nos endpoints sensíveis
  ✅ Validação de ownership em approve/reject reversal

Testes
  ✅ Unit: Domain (Entities, VOs, Services)
  ✅ Feature: todos os endpoints cobertos
  ✅ Integration: fluxo completo end-to-end
  ✅ Cobertura mínima 80%

Infraestrutura
  ✅ Docker prod-ready (sem secrets hardcoded)
  ✅ .env.example atualizado
```
