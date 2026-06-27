<?php

namespace App\Application\DTOs;

final class TransferRequestDTO
{
    public function __construct(
        public readonly string $senderId,
        public readonly string $recipientId,
        public readonly float $amount,
        public readonly ?string $description = null,
    ) {}
}
