<?php

namespace App\Domain\Exceptions;

class UserNotFoundException extends DomainException
{
    public function __construct(string $identifier = '')
    {
        $message = $identifier
            ? "Usuário não encontrado: {$identifier}"
            : 'Usuário não encontrado';

        parent::__construct($message);
    }
}
