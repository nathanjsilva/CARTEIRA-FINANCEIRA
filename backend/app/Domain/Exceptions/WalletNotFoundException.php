<?php

namespace App\Domain\Exceptions;

class WalletNotFoundException extends DomainException
{
    public function __construct(string $userId)
    {
        parent::__construct("Carteira não encontrada para o usuário: {$userId}");
    }
}
