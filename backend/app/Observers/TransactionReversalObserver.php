<?php

namespace App\Observers;

use App\Models\TransactionReversal;
use Illuminate\Support\Facades\Log;

class TransactionReversalObserver
{
    public function creating(TransactionReversal $reversal): void
    {
        if (!$reversal->status) {
            $reversal->status = 'pending';
        }

        Log::info('Solicitação de reversão criada', [
            'original_transaction_id' => $reversal->original_transaction_id,
            'reason'       => $reversal->reason,
            'requested_by' => $reversal->requested_by,
        ]);
    }

    public function created(TransactionReversal $reversal): void
    {
        Log::info('Reversão registrada', [
            'reversal_id' => $reversal->id,
            'uuid'        => $reversal->uuid,
            'status'      => $reversal->status,
        ]);
    }

    public function updating(TransactionReversal $reversal): void
    {
        Log::info('Reversão sendo atualizada', [
            'reversal_id' => $reversal->id,
            'old_status'  => $reversal->getOriginal('status'),
            'new_status'  => $reversal->status,
        ]);
    }

    public function updated(TransactionReversal $reversal): void
    {
        Log::info('Reversão atualizada', [
            'reversal_id' => $reversal->id,
            'status'      => $reversal->status,
        ]);
    }

    public function deleting(TransactionReversal $reversal): void
    {
        if ($reversal->status === 'approved') {
            throw new \RuntimeException('Não é possível excluir reversões aprovadas');
        }

        Log::warning('Reversão sendo excluída', [
            'reversal_id' => $reversal->id,
        ]);
    }
}
