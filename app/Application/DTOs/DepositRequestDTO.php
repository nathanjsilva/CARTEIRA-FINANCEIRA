<?php

namespace App\Application\DTOs;

final class DepositRequestDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly float $amount,
        public readonly ?string $description = null,
    ) {}
}
