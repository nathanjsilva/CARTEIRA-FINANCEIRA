<?php

namespace Tests\Unit\Observers;

use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TransactionReversalObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_reversal_observer_sets_default_status()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);

        Log::shouldReceive('info')->once()->with('Transaction reversal being created', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction reversal created successfully', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Notifying administrators about reversal request', \Mockery::type('array'));

        $reversal = TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'status' => null
        ]);

        // Verify default status was set
        $this->assertEquals('pending', $reversal->status);
    }

    public function test_reversal_observer_notifies_administrators()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);

        Log::shouldReceive('info')->once()->with('Transaction reversal being created', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction reversal created successfully', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Notifying administrators about reversal request', \Mockery::type('array'));

        TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'status' => 'pending'
        ]);
    }

    public function test_reversal_observer_validates_status_transitions()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);
        $reversal = TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'status' => 'approved'
        ]);

        Log::shouldReceive('info')->once()->with('Transaction reversal being updated', \Mockery::type('array'));
        Log::shouldReceive('error')->once()->with('Invalid reversal status transition', \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition from approved to pending');

        $reversal->update(['status' => 'pending']);
    }

    public function test_reversal_observer_handles_approval()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);
        $reversal = TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'status' => 'pending'
        ]);

        Log::shouldReceive('info')->once()->with('Transaction reversal being updated', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction reversal updated successfully', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction reversal approved', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Executing transaction reversal', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Notifying user about reversal approval', \Mockery::type('array'));

        $reversal->update(['status' => 'approved']);

        // Verify approved_at was set
        $this->assertNotNull($reversal->fresh()->approved_at);
    }

    public function test_reversal_observer_handles_rejection()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);
        $reversal = TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'status' => 'pending'
        ]);

        Log::shouldReceive('info')->once()->with('Transaction reversal being updated', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction reversal updated successfully', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction reversal rejected', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Notifying user about reversal rejection', \Mockery::type('array'));

        $reversal->update(['status' => 'rejected']);
    }

    public function test_reversal_observer_prevents_deletion_of_approved_reversals()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);
        $reversal = TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'status' => 'approved'
        ]);

        Log::shouldReceive('warning')->once()->with('Transaction reversal being deleted', \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete approved reversals');

        $reversal->delete();
    }

    public function test_reversal_observer_allows_deletion_of_pending_reversals()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);
        $reversal = TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'status' => 'pending'
        ]);

        Log::shouldReceive('warning')->once()->with('Transaction reversal being deleted', \Mockery::type('array'));
        Log::shouldReceive('warning')->once()->with('Transaction reversal deleted successfully', \Mockery::type('array'));

        $reversal->delete();

        $this->assertDatabaseMissing('transaction_reversals', [
            'id' => $reversal->id
        ]);
    }

    public function test_reversal_observer_executes_reversal_on_approval()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);
        
        // Create reversal transaction
        $reversalTransaction = Transaction::factory()->create([
            'type' => 'reversal',
            'status' => 'pending'
        ]);

        $reversal = TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'reversal_transaction_id' => $reversalTransaction->id,
            'status' => 'pending'
        ]);

        Log::shouldReceive('info')->times(6);

        $reversal->update(['status' => 'approved']);

        // Verify original transaction was marked as reversed
        $this->assertEquals('reversed', $transaction->fresh()->status);
        
        // Verify reversal transaction was completed
        $this->assertEquals('completed', $reversalTransaction->fresh()->status);
        
        // Verify reversal was completed
        $this->assertEquals('completed', $reversal->fresh()->status);
    }
}
