<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Transaction;

interface TransactionRepository
{
    public function save(Transaction $transaction): void;

    public function findById(string $id): ?Transaction;

    public function findByUuid(string $uuid): ?Transaction;

    public function findByUserId(string $userId, int $limit = 50): array;
}
