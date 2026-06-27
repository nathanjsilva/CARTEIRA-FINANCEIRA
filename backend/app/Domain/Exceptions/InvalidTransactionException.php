<?php

namespace App\Domain\Exceptions;

class InvalidTransactionException extends DomainException
{
    public function __construct(string $message = 'Operação de transação inválida')
    {
        parent::__construct($message);
    }
}
