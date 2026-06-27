<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidTransactionException;

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
    public static function failed(): self    { return new self('failed'); }
    public static function reversed(): self  { return new self('reversed'); }

    public static function from(string $value): self
    {
        if (!array_key_exists($value, self::VALID_TRANSITIONS)) {
            throw new \DomainException("Status de transação inválido: {$value}");
        }
        return new self($value);
    }

    public function transitionTo(self $next): self
    {
        $allowed = self::VALID_TRANSITIONS[$this->value] ?? [];
        if (!in_array($next->value, $allowed, true)) {
            throw new InvalidTransactionException(
                "Transição de status inválida: {$this->value} → {$next->value}"
            );
        }
        return $next;
    }

    public function is(string $value): bool { return $this->value === $value; }
    public function __toString(): string     { return $this->value; }
}
