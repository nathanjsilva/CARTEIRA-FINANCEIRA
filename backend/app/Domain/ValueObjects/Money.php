<?php

namespace App\Domain\ValueObjects;

final class Money
{
    private function __construct(private readonly int $cents) {}

    public static function of(float $amount): self
    {
        $cents = (int) round($amount * 100);
        if ($cents < 0) {
            throw new \DomainException("O valor monetário não pode ser negativo: R$ {$amount}");
        }
        return new self($cents);
    }

    /** Permite saldo negativo — usar apenas ao carregar do banco. */
    public static function ofBalance(float $amount): self
    {
        return new self((int) round($amount * 100));
    }

    public static function zero(): self { return new self(0); }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(Money $other): self
    {
        $result = $this->cents - $other->cents;
        if ($result < 0) {
            throw new \DomainException('Operação resultaria em saldo negativo');
        }
        return new self($result);
    }

    public function isGreaterOrEqual(Money $other): bool { return $this->cents >= $other->cents; }
    public function isGreaterThan(Money $other): bool    { return $this->cents > $other->cents; }
    public function isNegative(): bool                   { return $this->cents < 0; }
    public function equals(Money $other): bool           { return $this->cents === $other->cents; }
    public function getCents(): int                      { return $this->cents; }
    public function getAmount(): float                   { return $this->cents / 100; }
    public function format(): string
    {
        return 'R$ ' . number_format($this->getAmount(), 2, ',', '.');
    }
}
