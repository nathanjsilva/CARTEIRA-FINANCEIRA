<?php

namespace App\Application\Services\Transaction;

use App\Domain\Exceptions\InvalidTransactionException;
use App\Domain\Exceptions\WalletNotFoundException;
use App\Domain\Repositories\TransactionRepository;
use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TransactionReversalService
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
    ) {}

    public function requestReversal(
        Transaction $transaction,
        User $requestedBy,
        string $reason,
        ?string $description = null
    ): TransactionReversal {
        if (!$transaction->canBeReversed()) {
            throw new InvalidTransactionException('Transação não pode ser revertida. Apenas transferências concluídas são reversíveis.');
        }

        return DB::transaction(function () use ($transaction, $requestedBy, $reason, $description) {
            $reversalTransaction = Transaction::create([
                'from_wallet_id' => $transaction->to_wallet_id,
                'to_wallet_id'   => $transaction->from_wallet_id,
                'type'           => 'reversal',
                'amount'         => $transaction->amount,
                'currency'       => $transaction->currency,
                'status'         => 'pending',
                'description'    => "Reversão da transação {$transaction->uuid}",
                'reference_id'   => $transaction->uuid,
            ]);

            $reversal = TransactionReversal::create([
                'original_transaction_id' => $transaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
                'requested_by'            => $requestedBy->id,
                'reason'                  => $reason,
                'description'             => $description,
                'status'                  => 'pending',
            ]);

            Log::info('Reversão solicitada', [
                'original_transaction_id' => $transaction->id,
                'reversal_transaction_id' => $reversalTransaction->id,
                'requested_by'            => $requestedBy->id,
                'reason'                  => $reason,
            ]);

            return $reversal;
        });
    }

    public function approveReversal(TransactionReversal $reversal, User $approver): void
    {
        if (!$reversal->isPending()) {
            throw new InvalidTransactionException('Reversão não está pendente');
        }

        DB::transaction(function () use ($reversal, $approver) {
            $originalTransaction = $reversal->originalTransaction;
            $reversalTransaction = $reversal->reversalTransaction;

            $reversal->approve($approver);
            $this->executeReversal($originalTransaction, $reversalTransaction);
            $reversal->markAsCompleted();

            Log::info('Reversão aprovada e executada', [
                'reversal_id'             => $reversal->id,
                'original_transaction_id' => $originalTransaction->id,
                'approved_by'             => $approver->id,
            ]);
        });
    }

    public function rejectReversal(TransactionReversal $reversal): void
    {
        if (!$reversal->isPending()) {
            throw new InvalidTransactionException('Reversão não está pendente');
        }

        $reversal->reject();
        $reversal->reversalTransaction->markAsFailed();

        Log::info('Reversão rejeitada', [
            'reversal_id'             => $reversal->id,
            'original_transaction_id' => $reversal->original_transaction_id,
        ]);
    }

    private function executeReversal(Transaction $originalTransaction, Transaction $reversalTransaction): void
    {
        $fromWallet = $originalTransaction->fromWallet;
        $toWallet   = $originalTransaction->toWallet;

        if (!$fromWallet || !$toWallet) {
            throw new WalletNotFoundException('');
        }

        if (!$fromWallet->is_active || !$toWallet->is_active) {
            throw new InvalidTransactionException('Uma ou ambas as carteiras estão inativas');
        }

        if (!$toWallet->canWithdraw($reversalTransaction->amount)) {
            throw new InvalidTransactionException('Saldo insuficiente na carteira de destino para reversão');
        }

        $toWallet->withdraw($reversalTransaction->amount);
        $fromWallet->deposit($reversalTransaction->amount);

        $originalTransaction->markAsReversed();
        $reversalTransaction->markAsCompleted();

        Log::info('Reversão executada com sucesso', [
            'original_transaction_id' => $originalTransaction->id,
            'reversal_transaction_id' => $reversalTransaction->id,
        ]);
    }
}
