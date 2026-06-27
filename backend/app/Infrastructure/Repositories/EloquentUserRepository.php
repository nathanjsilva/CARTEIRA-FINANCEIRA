<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\User as UserEntity;
use App\Domain\ValueObjects\Money;
use App\Domain\Repositories\UserRepository;
use App\Models\User as UserModel;

final class EloquentUserRepository implements UserRepository
{
    public function create(UserEntity $user): UserModel
    {
        return UserModel::create([
            'name'     => $user->getName(),
            'email'    => $user->getEmail(),
            'password' => $user->getHashedPassword(),
        ]);
    }

    public function save(UserEntity $user): void
    {
        $model = UserModel::find($user->getId());
        if (!$model) {
            return;
        }

        $wallet = $model->getDefaultWallet();
        if ($wallet) {
            $wallet->update(['balance' => $user->getBalance()->getAmount()]);
        }
    }

    public function findById(string $id): ?UserEntity
    {
        $model = UserModel::with(['wallets' => fn ($q) => $q->where('currency', 'BRL')])->find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $model = UserModel::with(['wallets' => fn ($q) => $q->where('currency', 'BRL')])
            ->where('email', $email)
            ->first();
        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(UserModel $model): UserEntity
    {
        $wallet  = $model->wallets->first();
        $balance = Money::ofBalance((float) ($wallet?->balance ?? 0));

        return new UserEntity(
            id:             (string) $model->id,
            name:           $model->name,
            email:          $model->email,
            hashedPassword: $model->password,
            balance:        $balance,
        );
    }
}
