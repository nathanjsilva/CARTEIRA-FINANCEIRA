<?php

namespace App\Domain\ValueObjects;

final class Money
{
    private function __construct(private readonly float $amount)
    {
        if ($amount < 0) {
            throw new \DomainException('Money cannot be negative');
        }
    }

    public static function of(float $amount): self
    {
        return new self(round($amount, 2));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(Money $other): Money
    {
        return Money::of($this->amount + $other->amount);
    }

    public function subtract(Money $other): Money
    {
        return Money::of($this->amount - $other->amount);
    }

    public function isGreaterOrEqual(Money $other): bool
    {
        return $this->amount >= $other->amount;
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function format(): string
    {
        return number_format($this->amount, 2, ',', '.');
    }
}
