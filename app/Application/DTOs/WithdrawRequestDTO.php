<?php

namespace App\Application\DTOs;

final class WithdrawRequestDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly float $amount,
        public readonly ?string $description = null,
    ) {}
}
