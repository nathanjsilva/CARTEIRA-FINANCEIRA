<?php

namespace App\Observers;

use App\Models\TransactionReversal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransactionReversalObserver
{
    /**
     * Handle the TransactionReversal "creating" event.
     */
    public function creating(TransactionReversal $reversal): void
    {
        Log::info('Transaction reversal being created', [
            'original_transaction_id' => $reversal->original_transaction_id,
            'reason' => $reversal->reason,
            'requested_by' => $reversal->requested_by
        ]);

        // Set default status if not provided
        if (!$reversal->status) {
            $reversal->status = 'pending';
        }
    }

    /**
     * Handle the TransactionReversal "created" event.
     */
    public function created(TransactionReversal $reversal): void
    {
        Log::info('Transaction reversal created successfully', [
            'reversal_id' => $reversal->id,
            'uuid' => $reversal->uuid,
            'original_transaction_id' => $reversal->original_transaction_id,
            'reason' => $reversal->reason,
            'status' => $reversal->status
        ]);

        // Notify administrators about new reversal request
        if ($reversal->status === 'pending') {
            $this->notifyAdministrators($reversal);
        }
    }

    /**
     * Handle the TransactionReversal "updating" event.
     */
    public function updating(TransactionReversal $reversal): void
    {
        $oldStatus = $reversal->getOriginal('status');
        $newStatus = $reversal->status;

        Log::info('Transaction reversal being updated', [
            'reversal_id' => $reversal->id,
            'uuid' => $reversal->uuid,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changes' => $reversal->getDirty()
        ]);

        // Validate status transitions
        $this->validateStatusTransition($reversal, $oldStatus, $newStatus);

        // Set approval timestamp when approved
        if ($newStatus === 'approved' && $oldStatus === 'pending') {
            $reversal->approved_at = now();
        }
    }

    /**
     * Handle the TransactionReversal "updated" event.
     */
    public function updated(TransactionReversal $reversal): void
    {
        Log::info('Transaction reversal updated successfully', [
            'reversal_id' => $reversal->id,
            'uuid' => $reversal->uuid,
            'updated_fields' => array_keys($reversal->getDirty())
        ]);

        // Handle status changes
        if ($reversal->wasChanged('status')) {
            $this->handleStatusChange($reversal);
        }
    }

    /**
     * Handle the TransactionReversal "deleting" event.
     */
    public function deleting(TransactionReversal $reversal): void
    {
        Log::warning('Transaction reversal being deleted', [
            'reversal_id' => $reversal->id,
            'uuid' => $reversal->uuid,
            'status' => $reversal->status
        ]);

        // Prevent deletion of approved reversals
        if ($reversal->status === 'approved') {
            throw new \Exception('Cannot delete approved reversals');
        }
    }

    /**
     * Handle the TransactionReversal "deleted" event.
     */
    public function deleted(TransactionReversal $reversal): void
    {
        Log::warning('Transaction reversal deleted successfully', [
            'reversal_id' => $reversal->id,
            'uuid' => $reversal->uuid
        ]);
    }

    /**
     * Validate status transitions
     */
    private function validateStatusTransition(TransactionReversal $reversal, string $oldStatus, string $newStatus): void
    {
        $validTransitions = [
            'pending' => ['approved', 'rejected'],
            'approved' => ['completed'],
            'rejected' => [],
            'completed' => []
        ];

        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            Log::error('Invalid reversal status transition', [
                'reversal_id' => $reversal->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
            throw new \Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
        }
    }

    /**
     * Handle status changes
     */
    private function handleStatusChange(TransactionReversal $reversal): void
    {
        switch ($reversal->status) {
            case 'approved':
                $this->handleApprovedReversal($reversal);
                break;
            case 'rejected':
                $this->handleRejectedReversal($reversal);
                break;
            case 'completed':
                $this->handleCompletedReversal($reversal);
                break;
        }
    }

    /**
     * Handle approved reversal
     */
    private function handleApprovedReversal(TransactionReversal $reversal): void
    {
        Log::info('Transaction reversal approved', [
            'reversal_id' => $reversal->id,
            'approved_by' => $reversal->approved_by,
            'approved_at' => $reversal->approved_at
        ]);

        // Execute the reversal
        $this->executeReversal($reversal);

        // Notify user about approval
        $this->notifyUserApproval($reversal);
    }

    /**
     * Handle rejected reversal
     */
    private function handleRejectedReversal(TransactionReversal $reversal): void
    {
        Log::info('Transaction reversal rejected', [
            'reversal_id' => $reversal->id,
            'approved_by' => $reversal->approved_by
        ]);

        // Notify user about rejection
        $this->notifyUserRejection($reversal);
    }

    /**
     * Handle completed reversal
     */
    private function handleCompletedReversal(TransactionReversal $reversal): void
    {
        Log::info('Transaction reversal completed', [
            'reversal_id' => $reversal->id
        ]);

        // Notify user about completion
        $this->notifyUserCompletion($reversal);
    }

    /**
     * Execute the reversal
     */
    private function executeReversal(TransactionReversal $reversal): void
    {
        Log::info('Executing transaction reversal', [
            'reversal_id' => $reversal->id,
            'original_transaction_id' => $reversal->original_transaction_id
        ]);

        // Mark the reversal transaction as completed
        if ($reversal->reversalTransaction) {
            $reversal->reversalTransaction->update([
                'status' => 'completed',
                'processed_at' => now()
            ]);
        }

        // Mark the original transaction as reversed
        if ($reversal->originalTransaction) {
            $reversal->originalTransaction->update(['status' => 'reversed']);
        }

        // Update reversal status to completed
        $reversal->update(['status' => 'completed']);
    }

    /**
     * Notify administrators about new reversal request
     */
    private function notifyAdministrators(TransactionReversal $reversal): void
    {
        Log::info('Notifying administrators about reversal request', [
            'reversal_id' => $reversal->id
        ]);

        // In a real application, this would send notifications to administrators
        // Mail::to('admin@example.com')->send(new ReversalRequestNotification($reversal));
    }

    /**
     * Notify user about approval
     */
    private function notifyUserApproval(TransactionReversal $reversal): void
    {
        Log::info('Notifying user about reversal approval', [
            'reversal_id' => $reversal->id,
            'user_id' => $reversal->requested_by
        ]);

        // In a real application, this would send notifications to the user
    }

    /**
     * Notify user about rejection
     */
    private function notifyUserRejection(TransactionReversal $reversal): void
    {
        Log::info('Notifying user about reversal rejection', [
            'reversal_id' => $reversal->id,
            'user_id' => $reversal->requested_by
        ]);

        // In a real application, this would send notifications to the user
    }

    /**
     * Notify user about completion
     */
    private function notifyUserCompletion(TransactionReversal $reversal): void
    {
        Log::info('Notifying user about reversal completion', [
            'reversal_id' => $reversal->id,
            'user_id' => $reversal->requested_by
        ]);

        // In a real application, this would send notifications to the user
    }
}
