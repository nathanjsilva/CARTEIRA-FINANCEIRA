# SDD: Arquitetura Alvo (DDD)

> Referência cruzada: [[SDD_CONTEXTO_GERAL]] | [[SDD_ROTEIRO_REFATORACAO]]

---

## 1. Visão Geral

A arquitetura alvo formaliza o DDD já iniciado no projeto, eliminando as inconsistências híbridas identificadas. O objetivo é uma separação limpa em 4 camadas: **Domain** (regras de negócio puras), **Application** (casos de uso/orquestração), **Infrastructure** (implementações técnicas) e **Presentation** (entrada HTTP). Nenhuma camada interna conhece a camada que a usa.

```
┌─────────────────────────────────────────────────────┐
│                   Presentation                      │
│        (Controllers, FormRequests, Resources)       │
├─────────────────────────────────────────────────────┤
│                   Application                       │
│          (Services, DTOs, Events, Commands)         │
├─────────────────────────────────────────────────────┤
│                     Domain                          │
│    (Entities, ValueObjects, Repositories, Exceptions)│
├─────────────────────────────────────────────────────┤
│                  Infrastructure                     │
│       (Eloquent Repos, Cache, Queue, External)      │
└─────────────────────────────────────────────────────┘
           ↓ dependência permitida
           ↑ dependência PROIBIDA
```

**Regra de dependência:** camadas externas dependem das internas; nunca o contrário. Domain não conhece Laravel, Eloquent, nem HTTP.

---

## 2. Estrutura de Diretórios Alvo

```
backend/app/
├── Domain/
│   ├── Entities/
│   │   ├── User.php
│   │   ├── Wallet.php          ← nova (hoje só no Eloquent)
│   │   └── Transaction.php
│   ├── ValueObjects/
│   │   ├── Money.php           ← migrar de float para int (centavos)
│   │   ├── Email.php           ← já existe, usar em User
│   │   ├── Currency.php        ← novo: encapsula 'BRL', validação
│   │   └── TransactionStatus.php ← novo: enum-like com transições válidas
│   ├── Repositories/           ← só interfaces
│   │   ├── UserRepository.php
│   │   ├── WalletRepository.php
│   │   └── TransactionRepository.php
│   └── Exceptions/
│       ├── DomainException.php
│       ├── InsufficientFundsException.php
│       ├── UserNotFoundException.php
│       ├── WalletNotFoundException.php     ← novo
│       └── InvalidTransactionException.php ← novo (substitui Observer throw)
│
├── Application/
│   ├── Services/
│   │   ├── Auth/
│   │   │   ├── RegisterUserService.php    ← novo (tirar do Controller)
│   │   │   └── LoginService.php           ← novo
│   │   ├── Wallet/
│   │   │   ├── DepositService.php
│   │   │   └── WithdrawService.php
│   │   └── Transaction/
│   │       ├── TransferService.php
│   │       └── TransactionReversalService.php ← mover de app/Services/
│   ├── DTOs/
│   │   ├── Auth/
│   │   │   ├── RegisterRequestDTO.php
│   │   │   └── AuthResponseDTO.php
│   │   ├── Wallet/
│   │   │   ├── DepositRequestDTO.php
│   │   │   └── WithdrawRequestDTO.php
│   │   └── Transaction/
│   │       ├── TransferRequestDTO.php
│   │       ├── ReversalRequestDTO.php
│   │       └── TransactionResponseDTO.php
│   └── Events/
│       ├── TransactionCompleted.php       ← novo
│       ├── TransactionReversalRequested.php
│       └── HighValueTransactionDetected.php ← extrair do Observer
│
├── Infrastructure/
│   ├── Repositories/
│   │   ├── EloquentUserRepository.php
│   │   ├── EloquentWalletRepository.php   ← novo
│   │   └── EloquentTransactionRepository.php
│   ├── Cache/
│   │   └── RedisWalletBalanceCache.php    ← novo: cache de saldo
│   └── Observers/                         ← mantém, mas sem lógica de negócio
│       ├── TransactionObserver.php        ← só logging/eventos
│       └── UserObserver.php
│
└── Presentation/
    └── Http/
        ├── Controllers/
        │   ├── AuthController.php
        │   ├── WalletController.php
        │   └── TransactionController.php
        ├── Requests/                      ← novo: FormRequest para cada operação
        │   ├── RegisterRequest.php
        │   ├── LoginRequest.php
        │   ├── DepositRequest.php
        │   ├── WithdrawRequest.php
        │   ├── TransferRequest.php
        │   └── ReversalRequest.php
        └── Resources/                    ← novo: API Resources para formato consistente
            ├── UserResource.php
            ├── WalletResource.php
            └── TransactionResource.php
```

---

## 3. Design Principles Obrigatórios

### SOLID
- **S** — Cada classe tem uma responsabilidade. Observer loga; Service orquestra; Entity muta estado
- **O** — Novos tipos de transação = nova implementação, não modificação de `TransactionService`
- **L** — Todos os repositories são substituíveis pela interface
- **I** — Repositórios segregados: `UserRepository`, `WalletRepository`, `TransactionRepository`
- **D** — Controllers e Services dependem de interfaces, não de classes Eloquent

### Domain Rules
- Entities são objetos com identidade; ValueObjects são imutáveis e sem identidade
- Toda regra de negócio vive no Domain (ex: `canTransfer`, transições de status)
- Services de Application orquestram, nunca implementam regras de negócio

---

## 4. Melhorias Técnicas Prioritárias

### 4.1 Money: float → integer (centavos)

**Problema atual:** `float` tem imprecisão binária — ex: `0.1 + 0.2 ≠ 0.3`

**Solução alvo:**
```php
// Domain/ValueObjects/Money.php
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
        return new self((int) round($amount * 100));
    }

    public function add(Money $other): Money
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(Money $other): Money
    {
        $result = $this->cents - $other->cents;
        if ($result < 0) throw new \DomainException('Money cannot be negative');
        return new self($result);
    }

    public function toFloat(): float { return $this->cents / 100; }
    public function getCents(): int  { return $this->cents; }
    public function format(): string { return 'R$ ' . number_format($this->toFloat(), 2, ',', '.'); }
}
```

### 4.2 TransactionStatus: centralizar máquina de estados

**Problema atual:** transições válidas estão no Observer (efeito colateral) — lógica de negócio no lugar errado

**Solução alvo:**
```php
// Domain/ValueObjects/TransactionStatus.php
final class TransactionStatus
{
    private const VALID_TRANSITIONS = [
        'pending'   => ['completed', 'failed'],
        'completed' => ['reversed'],
        'failed'    => [],
        'reversed'  => [],
    ];

    private function __construct(public readonly string $value) {}

    public static function pending(): self   { return new self('pending'); }
    public static function completed(): self { return new self('completed'); }

    public function transitionTo(self $next): self
    {
        $allowed = self::VALID_TRANSITIONS[$this->value] ?? [];
        if (!in_array($next->value, $allowed)) {
            throw new InvalidTransactionException(
                "Invalid status transition: {$this->value} → {$next->value}"
            );
        }
        return $next;
    }
}
```

### 4.3 Application Services de Auth

**Problema atual:** `AuthController` usa `User::create()` diretamente — sem domain

**Solução alvo:**
```php
// Application/Services/Auth/RegisterUserService.php
final class RegisterUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly WalletRepository $walletRepository,
    ) {}

    public function execute(RegisterRequestDTO $dto): AuthResponseDTO
    {
        $email = Email::of($dto->email);  // valida formato
        $user  = User::register(
            id:             (string) Str::uuid(),
            name:           $dto->name,
            email:          $email->getValue(),
            hashedPassword: Hash::make($dto->password),
        );
        $this->userRepository->create($user);

        $wallet = Wallet::createDefault($user->getId());
        $this->walletRepository->create($wallet);

        return new AuthResponseDTO($user, $wallet);
    }
}
```

### 4.4 FormRequests

**Problema atual:** validação inline nos Controllers — duplicação, sem reutilização

**Solução alvo:**
```php
// Presentation/Http/Requests/TransferRequest.php
class TransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'exists:users,id'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'description'  => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

### 4.5 API Resources

**Problema atual:** transformação de dados inline nos Controllers

**Solução alvo:**
```php
// Presentation/Http/Resources/TransactionResource.php
class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->uuid,
            'type'       => $this->type,
            'amount'     => (float) $this->amount,
            'currency'   => $this->currency,
            'status'     => $this->status,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

---

## 5. Diagrama de Fluxo: Transferência (Estado Alvo)

```
HTTP Request
    │
    ▼
TransferRequest (FormRequest — valida, sanitiza)
    │
    ▼
TransactionController::transfer()
    │  injeta TransferService via DI
    ▼
TransferService::execute(TransferRequestDTO)
    │
    ├─ UserRepository::findById(senderId)    ──► EloquentUserRepository
    ├─ UserRepository::findById(recipientId) ──► EloquentUserRepository
    │
    ├─ Money::fromFloat(amount)
    ├─ sender.canTransfer(amount) ──► InsufficientFundsException
    │
    └─ DB::transaction {
           sender.transfer(amount, recipient)     [domain mutation]
           UserRepository::save(sender)
           UserRepository::save(recipient)
           transaction = Transaction::transfer(...)
           transaction.complete()
           TransactionRepository::save(transaction)
           Event::dispatch(new TransactionCompleted($transaction))
       }
           │
           ▼
       TransactionCompleted listener ──► Log, Notificação, Auditoria
           │
           ▼
TransactionResource::make($transaction)
    │
    ▼
JSON 201 Response
```

---

## 6. Estratégia de Testes

| Tipo | O que testa | Ferramentas | Alvo |
|------|-------------|-------------|------|
| Unit | Domain Entities, ValueObjects, Services (mock repos) | PHPUnit + Mockery | 100% coverage |
| Integration | Repository implementações com BD real | PHPUnit + SQLite in-memory | Queries corretas |
| Feature | Endpoints HTTP (request → response) | Laravel HTTP tests | Happy path + erros |
| Contract | Formato de resposta da API | PHPUnit assertions | Estabilidade de contrato |

```php
// Exemplo: Unit test para Money (centavos)
test('soma dois valores sem imprecisão float', function () {
    $a = Money::fromFloat(0.1);
    $b = Money::fromFloat(0.2);
    expect($a->add($b)->toFloat())->toBe(0.3);
});

// Exemplo: Unit test para transferência
test('lança exceção quando saldo insuficiente', function () {
    $sender = User::register('1', 'Alice', 'a@a.com', 'hash');
    $sender->deposit(Money::fromFloat(10.00));

    $this->expectException(InsufficientFundsException::class);
    $sender->transfer(Money::fromFloat(50.00), User::register('2', 'Bob', 'b@b.com', 'hash'));
});
```

---

## 7. Cache Strategy

```
GET /v1/wallet/balance
    → Redis key: "wallet:balance:{userId}"
    → TTL: 60 segundos
    → Invalidar em: depósito, saque, transferência recebida

Implementação:
    WalletController::balance()
        → RedisWalletBalanceCache::get(userId) ?? WalletRepository::getBalance(userId)
        → store no Redis se cache miss
```

---

## 8. Security Checklist

- [x] Autenticação via Bearer token (Sanctum)
- [x] IDs internos nunca expostos na API (uuid público)
- [x] Senhas hasheadas com bcrypt
- [x] Transações financeiras em DB::transaction()
- [ ] Rate limiting nos endpoints de auth e transferência
- [ ] Validação de ownership antes de aprovar/rejeitar reversão (qualquer user pode hoje)
- [ ] Sanitização de `metadata` JSON antes de persistir
- [ ] HTTPS enforced em produção (nginx config)
- [ ] Secrets em variáveis de ambiente (não hardcoded no docker-compose)

---

## 9. Performance Targets

| Operação | Target p95 | Estratégia |
|----------|-----------|-----------|
| GET /balance | < 50ms | Redis cache |
| POST /deposit | < 200ms | DB transaction rápida |
| POST /transfer | < 300ms | DB transaction + índices |
| GET /history | < 150ms | Índice composto em wallets + paginação |
| POST /register | < 500ms | Aceitável (escrita rara) |

---

## 10. Deployment Strategy

```
docker-compose.yml (desenvolvimento)
    app       → PHP-FPM
    nginx     → proxy reverso :8000
    frontend  → Vite dev server :5173
    db        → MySQL :3306
    redis     → Redis :6379

Produção (evolução sugerida):
    → Laravel Octane (Swoole) para latência menor
    → MySQL read replica para queries de histórico
    → Redis Cluster para cache e queue
    → Horizon para monitorar queues
    → CI/CD: GitHub Actions → build → push imagem → deploy
```
