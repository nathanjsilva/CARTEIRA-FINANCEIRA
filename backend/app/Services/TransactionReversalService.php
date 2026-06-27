<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TransactionReversalService
{
    public function requestReversal(
        Transaction $transaction,
        User $requestedBy,
        string $reason,
        string $description = null
    ): TransactionReversal {
        if (!$transaction->canBeReversed()) {
            throw new Exception('Transação não pode ser revertida');
        }

        return DB::transaction(function () use ($transaction, $requestedBy, $reason, $description) {
            // Criar transação de reversão
            $reversalTransaction = Transaction::create([
                'from_wallet_id' => $transaction->to_wallet_id,
                'to_wallet_id' => $transaction->from_wallet_id,
                'type' => 'reversal',
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => 'pending',
                'description' => "Reversão da transação {$transaction->uuid}",
                'reference_id' => $transaction->uuid,
            ]);

            // Criar solicitação de reversão
            $reversal = TransactionReversal::create([
                'original_transaction_id' => $transaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
                'requested_by' => $requestedBy->id,
                'reason' => $reason,
                'description' => $description,
                'status' => 'pending',
            ]);

            Log::info('Reversão solicitada', [
                'original_transaction_id' => $transaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
                'requested_by' => $requestedBy->id,
                'reason' => $reason,
            ]);

            return $reversal;
        });
    }

    public function approveReversal(TransactionReversal $reversal, User $approver): void
    {
        if (!$reversal->isPending()) {
            throw new Exception('Reversão não está pendente');
        }

        DB::transaction(function () use ($reversal, $approver) {
            $originalTransaction = $reversal->originalTransaction;
            $reversalTransaction = $reversal->reversalTransaction;

            // Approve the reversal
            $reversal->approve($approver);

            // Execute the reversal
            $this->executeReversal($originalTransaction, $reversalTransaction);

            // Mark reversal as completed
            $reversal->markAsCompleted();

            Log::info('Reversal approved and executed', [
                'reversal_id' => $reversal->id,
                'original_transaction_id' => $originalTransaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
                'approved_by' => $approver->id,
            ]);
        });
    }

    public function rejectReversal(TransactionReversal $reversal): void
    {
        if (!$reversal->isPending()) {
            throw new Exception('Reversão não está pendente');
        }

        $reversal->reject();

        // Mark reversal transaction as failed
        $reversal->reversalTransaction->markAsFailed();

        Log::info('Reversal rejected', [
            'reversal_id' => $reversal->id,
            'original_transaction_id' => $reversal->original_transaction_id,
        ]);
    }

    private function executeReversal(Transaction $originalTransaction, Transaction $reversalTransaction): void
    {
        try {
            // Get wallets
            $fromWallet = $originalTransaction->fromWallet;
            $toWallet = $originalTransaction->toWallet;

            if (!$fromWallet || !$toWallet) {
                throw new Exception('Carteiras da transação original não encontradas');
            }

            // Check if reversal wallets are active
            if (!$fromWallet->is_active || !$toWallet->is_active) {
                throw new Exception('Uma ou ambas as carteiras estão inativas');
            }

            // Check if destination wallet has sufficient balance
            if (!$toWallet->canWithdraw($reversalTransaction->amount)) {
                throw new Exception('Saldo insuficiente na carteira de destino para reversão');
            }

            // Execute the reversal
            $toWallet->withdraw($reversalTransaction->amount);
            $fromWallet->deposit($reversalTransaction->amount);

            // Mark transactions
            $originalTransaction->markAsReversed();
            $reversalTransaction->markAsCompleted();

            Log::info('Reversal executed successfully', [
                'original_transaction_id' => $originalTransaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
            ]);
        } catch (Exception $e) {
            $reversalTransaction->markAsFailed();
            Log::error('Reversal execution failed', [
                'original_transaction_id' => $originalTransaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getPendingReversals(): \Illuminate\Database\Eloquent\Collection
    {
        return TransactionReversal::where('status', 'pending')
            ->with(['originalTransaction', 'reversalTransaction', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getReversalHistory(User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = TransactionReversal::with([
            'originalTransaction',
            'reversalTransaction',
            'requestedBy',
            'approvedBy'
        ]);

        if ($user) {
            $query->where('requested_by', $user->id);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
