<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\TransactionStatus;

class Transaction
{
    public const TYPE_TRANSFER  = 'transfer';
    public const TYPE_DEPOSIT   = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_REVERSAL  = 'reversal';

    private TransactionStatus $status;
    private \DateTime $createdAt;

    public function __construct(
        private readonly string $id,
        private readonly string $senderId,
        private readonly string $recipientId,
        private readonly Money $amount,
        private readonly string $type,
        string $status = 'pending',
        \DateTime $createdAt = null
    ) {
        $this->status    = TransactionStatus::from($status);
        $this->createdAt = $createdAt ?? new \DateTime();
    }

    public static function transfer(string $id, string $senderId, string $recipientId, Money $amount): self
    {
        return new self($id, $senderId, $recipientId, $amount, self::TYPE_TRANSFER);
    }

    public static function deposit(string $id, string $userId, Money $amount): self
    {
        return new self($id, $userId, $userId, $amount, self::TYPE_DEPOSIT);
    }

    public static function withdrawal(string $id, string $userId, Money $amount): self
    {
        return new self($id, $userId, $userId, $amount, self::TYPE_WITHDRAWAL);
    }

    public function getId(): string        { return $this->id; }
    public function getSenderId(): string  { return $this->senderId; }
    public function getRecipientId(): string { return $this->recipientId; }
    public function getAmount(): Money     { return $this->amount; }
    public function getType(): string      { return $this->type; }
    public function getStatus(): string    { return $this->status->value; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }

    public function complete(): void
    {
        $this->status = $this->status->transitionTo(TransactionStatus::completed());
    }

    public function reverse(): void
    {
        $this->status = $this->status->transitionTo(TransactionStatus::reversed());
    }

    public function fail(): void
    {
        $this->status = $this->status->transitionTo(TransactionStatus::failed());
    }

    public function isCompleted(): bool
    {
        return $this->status->is('completed');
    }

    public function canBeReversed(): bool
    {
        return $this->status->is('completed') && $this->type === self::TYPE_TRANSFER;
    }
}
