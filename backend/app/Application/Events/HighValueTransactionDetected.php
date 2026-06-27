<?php

namespace App\Application\Events;

final class HighValueTransactionDetected
{
    public const THRESHOLD = 1000.0;

    public function __construct(
        public readonly string $transactionId,
        public readonly float $amount,
        public readonly string $type,
        public readonly string $userId,
    ) {}
}
