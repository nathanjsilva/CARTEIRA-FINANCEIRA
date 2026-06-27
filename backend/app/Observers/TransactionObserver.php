<?php

namespace App\Observers;

use App\Domain\ValueObjects\TransactionStatus;
use App\Domain\Exceptions\InvalidTransactionException;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    public function creating(Transaction $transaction): void
    {
        if (!$transaction->status) {
            $transaction->status = 'pending';
        }
        if (!$transaction->currency) {
            $transaction->currency = 'BRL';
        }

        Log::info('Transação sendo criada', [
            'type'           => $transaction->type,
            'amount'         => $transaction->amount,
            'from_wallet_id' => $transaction->from_wallet_id,
            'to_wallet_id'   => $transaction->to_wallet_id,
        ]);
    }

    public function created(Transaction $transaction): void
    {
        Log::info('Transação criada', [
            'transaction_id' => $transaction->id,
            'uuid'           => $transaction->uuid,
            'type'           => $transaction->type,
            'amount'         => $transaction->amount,
            'status'         => $transaction->status,
        ]);
    }

    public function updating(Transaction $transaction): void
    {
        $oldStatus = $transaction->getOriginal('status');
        $newStatus = $transaction->status;

        if ($oldStatus !== $newStatus) {
            try {
                TransactionStatus::from($oldStatus)->transitionTo(TransactionStatus::from($newStatus));
            } catch (InvalidTransactionException $e) {
                Log::error('Transição de status inválida bloqueada', [
                    'transaction_id' => $transaction->id,
                    'old_status'     => $oldStatus,
                    'new_status'     => $newStatus,
                ]);
                throw $e;
            }
        }

        Log::info('Transação sendo atualizada', [
            'transaction_id' => $transaction->id,
            'old_status'     => $oldStatus,
            'new_status'     => $newStatus,
        ]);
    }

    public function updated(Transaction $transaction): void
    {
        Log::info('Transação atualizada', [
            'transaction_id' => $transaction->id,
            'status'         => $transaction->status,
        ]);
    }

    public function deleting(Transaction $transaction): void
    {
        if ($transaction->status === 'completed') {
            throw new InvalidTransactionException('Não é possível excluir transações concluídas');
        }

        Log::warning('Transação sendo excluída', [
            'transaction_id' => $transaction->id,
            'status'         => $transaction->status,
        ]);
    }
}
