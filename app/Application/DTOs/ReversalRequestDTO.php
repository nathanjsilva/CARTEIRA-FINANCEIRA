<?php

namespace App\Application\DTOs;

final class ReversalRequestDTO
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $requestedById,
        public readonly string $reason,
        public readonly ?string $description = null,
    ) {}
}
