<?php

namespace App\Application\DTOs\Auth;

final readonly class AuthResponseDTO
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public float $balance,
        public ?string $walletId,
        public string $currency,
        public string $token,
    ) {}

    public function toArray(): array
    {
        return [
            'user' => [
                'id'    => $this->userId,
                'name'  => $this->name,
                'email' => $this->email,
            ],
            'wallet' => [
                'id'       => $this->walletId,
                'balance'  => $this->balance,
                'currency' => $this->currency,
            ],
            'token' => $this->token,
        ];
    }
}
