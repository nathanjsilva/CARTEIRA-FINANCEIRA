<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Money;
use App\Domain\Exceptions\DomainException;
use App\Domain\Exceptions\InsufficientFundsException;

class User
{
    private Money $balance;

    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $email,
        private string $hashedPassword,
        Money $balance = null
    ) {
        $this->balance = $balance ?? Money::zero();
    }

    public static function register(string $id, string $name, string $email, string $hashedPassword): self
    {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            hashedPassword: $hashedPassword,
            balance: Money::zero()
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function transfer(Money $amount, User $recipient): void
    {
        if ($this->id === $recipient->id) {
            throw new DomainException('Transferência para a própria conta não é permitida.');
        }

        if (!$this->canTransfer($amount)) {
            throw new InsufficientFundsException($amount, $this->balance);
        }

        $this->balance = $this->balance->subtract($amount);
        $recipient->balance = $recipient->balance->add($amount);
    }

    public function deposit(Money $amount): void
    {
        $this->balance = $this->balance->add($amount);
    }

    public function withdraw(Money $amount): void
    {
        if (!$this->canTransfer($amount)) {
            throw new InsufficientFundsException($amount, $this->balance);
        }
        $this->balance = $this->balance->subtract($amount);
    }

    public function canTransfer(Money $amount): bool
    {
        return $this->balance->isGreaterOrEqual($amount);
    }

    public function setBalance(Money $money): void
    {
        $this->balance = $money;
    }
}
