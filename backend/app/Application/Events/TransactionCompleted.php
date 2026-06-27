<?php

namespace App\Application\Events;

final class TransactionCompleted
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $type,
        public readonly float $amount,
        public readonly string $senderId,
        public readonly string $recipientId,
    ) {}
}
