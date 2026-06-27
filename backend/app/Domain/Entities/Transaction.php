<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Money;

class Transaction
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_FAILED = 'failed';

    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_REVERSAL = 'reversal';

    private string $status;
    private \DateTime $createdAt;

    public function __construct(
        private readonly string $id,
        private readonly string $senderId,
        private readonly string $recipientId,
        private readonly Money $amount,
        private readonly string $type,
        string $status = self::STATUS_PENDING,
        \DateTime $createdAt = null
    ) {
        $this->status = $status;
        $this->createdAt = $createdAt ?? new \DateTime();
    }

    public static function transfer(string $id, string $senderId, string $recipientId, Money $amount): self
    {
        return new self(
            id: $id,
            senderId: $senderId,
            recipientId: $recipientId,
            amount: $amount,
            type: self::TYPE_TRANSFER
        );
    }

    public static function deposit(string $id, string $userId, Money $amount): self
    {
        return new self(
            id: $id,
            senderId: $userId,
            recipientId: $userId,
            amount: $amount,
            type: self::TYPE_DEPOSIT
        );
    }

    public static function withdrawal(string $id, string $userId, Money $amount): self
    {
        return new self(
            id: $id,
            senderId: $userId,
            recipientId: $userId,
            amount: $amount,
            type: self::TYPE_WITHDRAWAL
        );
    }

    public function getId(): string { return $this->id; }
    public function getSenderId(): string { return $this->senderId; }
    public function getRecipientId(): string { return $this->recipientId; }
    public function getAmount(): Money { return $this->amount; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }

    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
    }

    public function reverse(): void
    {
        $this->status = self::STATUS_REVERSED;
    }

    public function fail(): void
    {
        $this->status = self::STATUS_FAILED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeReversed(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->type === self::TYPE_TRANSFER;
    }
}
