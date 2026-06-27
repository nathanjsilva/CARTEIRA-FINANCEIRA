<?php

namespace App\Domain\Exceptions;

use App\Domain\ValueObjects\Money;

class InsufficientFundsException extends DomainException
{
    public function __construct(Money $requested, Money $available)
    {
        parent::__construct(
            "Saldo insuficiente. Solicitado: R$ {$requested->format()}, Disponível: R$ {$available->format()}"
        );
    }
}
