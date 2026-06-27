<?php

namespace App\Application\DTOs\Auth;

final readonly class RegisterRequestDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
