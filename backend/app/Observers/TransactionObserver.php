<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransactionObserver
{
    /**
     * Manipula o evento "creating" da Transaction.
     */
    public function creating(Transaction $transaction): void
    {
        Log::info('Transação sendo criada', [
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'from_wallet_id' => $transaction->from_wallet_id,
            'to_wallet_id' => $transaction->to_wallet_id
        ]);

        // Definir status padrão se não fornecido
        if (!$transaction->status) {
            $transaction->status = 'pending';
        }

        // Definir moeda padrão se não fornecida
        if (!$transaction->currency) {
            $transaction->currency = 'BRL';
        }
    }

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        Log::info('Transação criada com sucesso', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'status' => $transaction->status
        ]);

        // Enviar notificação para transações de alto valor
        if ($transaction->amount > 1000) {
            $this->notifyHighValueTransaction($transaction);
        }

        // Processar transações pendentes automaticamente
        if ($transaction->status === 'pending') {
            $this->processPendingTransaction($transaction);
        }
    }

    /**
     * Handle the Transaction "updating" event.
     */
    public function updating(Transaction $transaction): void
    {
        $oldStatus = $transaction->getOriginal('status');
        $newStatus = $transaction->status;

        Log::info('Transaction being updated', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changes' => $transaction->getDirty()
        ]);

        // Validate status transitions
        $this->validateStatusTransition($transaction, $oldStatus, $newStatus);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        Log::info('Transaction updated successfully', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid,
            'updated_fields' => array_keys($transaction->getDirty())
        ]);

        // Handle status changes
        if ($transaction->wasChanged('status')) {
            $this->handleStatusChange($transaction);
        }
    }

    /**
     * Handle the Transaction "deleting" event.
     */
    public function deleting(Transaction $transaction): void
    {
        Log::warning('Transaction being deleted', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid,
            'type' => $transaction->type,
            'amount' => $transaction->amount
        ]);

        // Prevent deletion of completed transactions
        if ($transaction->status === 'completed') {
            throw new \Exception('Cannot delete completed transactions');
        }
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        Log::warning('Transaction deleted successfully', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid
        ]);
    }

    /**
     * Validate status transitions
     */
    private function validateStatusTransition(Transaction $transaction, string $oldStatus, string $newStatus): void
    {
        $validTransitions = [
            'pending' => ['completed', 'failed', 'cancelled'],
            'completed' => ['reversed'],
            'failed' => ['pending'],
            'cancelled' => [],
            'reversed' => []
        ];

        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            Log::error('Invalid transaction status transition', [
                'transaction_id' => $transaction->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
            throw new \Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
        }
    }

    /**
     * Handle status changes
     */
    private function handleStatusChange(Transaction $transaction): void
    {
        switch ($transaction->status) {
            case 'completed':
                $this->handleCompletedTransaction($transaction);
                break;
            case 'failed':
                $this->handleFailedTransaction($transaction);
                break;
            case 'reversed':
                $this->handleReversedTransaction($transaction);
                break;
        }
    }

    /**
     * Handle completed transaction
     */
    private function handleCompletedTransaction(Transaction $transaction): void
    {
        // Set processed timestamp
        if (!$transaction->processed_at) {
            $transaction->processed_at = now();
        }

        Log::info('Transaction completed', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid,
            'processed_at' => $transaction->processed_at
        ]);

        // Send completion notification
        $this->notifyTransactionCompletion($transaction);
    }

    /**
     * Handle failed transaction
     */
    private function handleFailedTransaction(Transaction $transaction): void
    {
        Log::warning('Transaction failed', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid,
            'type' => $transaction->type,
            'amount' => $transaction->amount
        ]);

        // Send failure notification
        $this->notifyTransactionFailure($transaction);
    }

    /**
     * Handle reversed transaction
     */
    private function handleReversedTransaction(Transaction $transaction): void
    {
        Log::info('Transaction reversed', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid
        ]);
    }

    /**
     * Process pending transaction
     */
    private function processPendingTransaction(Transaction $transaction): void
    {
        // For now, we'll just log it. In a real application, this could trigger
        // background job processing or immediate processing logic
        Log::info('Processing pending transaction', [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->uuid
        ]);
    }

    /**
     * Notify high value transaction
     */
    private function notifyHighValueTransaction(Transaction $transaction): void
    {
        Log::info('High value transaction detected', [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'type' => $transaction->type
        ]);

        // In a real application, this would send email/SMS notifications
        // Mail::to('admin@example.com')->send(new HighValueTransactionNotification($transaction));
    }

    /**
     * Notify transaction completion
     */
    private function notifyTransactionCompletion(Transaction $transaction): void
    {
        Log::info('Transaction completion notification sent', [
            'transaction_id' => $transaction->id
        ]);

        // In a real application, this would send notifications to users
    }

    /**
     * Notify transaction failure
     */
    private function notifyTransactionFailure(Transaction $transaction): void
    {
        Log::info('Transaction failure notification sent', [
            'transaction_id' => $transaction->id
        ]);

        // In a real application, this would send failure notifications
    }
}
