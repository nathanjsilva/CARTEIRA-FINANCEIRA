<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Transaction as TransactionEntity;
use App\Domain\ValueObjects\Money;
use App\Domain\Repositories\TransactionRepository;
use App\Models\Transaction as TransactionModel;
use App\Models\User as UserModel;

final class EloquentTransactionRepository implements TransactionRepository
{
    public function save(TransactionEntity $transaction): void
    {
        $fromWalletId = $this->getWalletId($transaction->getSenderId());
        $toWalletId   = $this->getWalletId($transaction->getRecipientId());

        TransactionModel::updateOrCreate(
            ['uuid' => $transaction->getId()],
            [
                'from_wallet_id' => $fromWalletId,
                'to_wallet_id'   => $toWalletId,
                'type'           => $transaction->getType(),
                'amount'         => $transaction->getAmount()->getAmount(),
                'currency'       => 'BRL',
                'status'         => $transaction->getStatus(),
                'processed_at'   => $transaction->isCompleted() ? now() : null,
            ]
        );
    }

    public function findById(string $id): ?TransactionEntity
    {
        $model = TransactionModel::where('uuid', $id)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByUserId(string $userId, int $limit = 50): array
    {
        $wallet = UserModel::find($userId)?->getDefaultWallet();
        if (!$wallet) {
            return [];
        }

        return TransactionModel::where('from_wallet_id', $wallet->id)
            ->orWhere('to_wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    private function toDomain(TransactionModel $model): TransactionEntity
    {
        $senderId    = (string) ($model->fromWallet?->user_id ?? $model->toWallet?->user_id ?? '');
        $recipientId = (string) ($model->toWallet?->user_id ?? $senderId);

        return new TransactionEntity(
            id: $model->uuid,
            senderId: $senderId,
            recipientId: $recipientId,
            amount: Money::of((float) $model->amount),
            type: $model->type,
            status: $model->status,
            createdAt: new \DateTime($model->created_at)
        );
    }

    private function getWalletId(string $userId): ?int
    {
        return UserModel::find($userId)?->getDefaultWallet()?->id;
    }
}
