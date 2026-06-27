<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\User;

interface UserRepository
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;

    /** Persiste novo usuário e retorna o Eloquent model para geração de token. */
    public function create(User $user): \App\Models\User;
}
