<?php

namespace Tests\Unit\Observers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TransactionObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_observer_sets_defaults_on_creation()
    {
        $wallet = Wallet::factory()->create();

        Log::shouldReceive('info')->once()->with('Transaction being created', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction created successfully', \Mockery::type('array'));

        $transaction = Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status' => null,
            'currency' => null
        ]);

        // Verify defaults were set
        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals('BRL', $transaction->currency);
    }

    public function test_transaction_observer_logs_high_value_transactions()
    {
        $wallet = Wallet::factory()->create();

        Log::shouldReceive('info')->once()->with('Transaction being created', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction created successfully', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('High value transaction detected', \Mockery::type('array'));

        Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'amount' => 1500.00
        ]);
    }

    public function test_transaction_observer_validates_status_transitions()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status' => 'completed'
        ]);

        Log::shouldReceive('info')->once()->with('Transaction being updated', \Mockery::type('array'));
        Log::shouldReceive('error')->once()->with('Invalid transaction status transition', \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition from completed to pending');

        $transaction->update(['status' => 'pending']);
    }

    public function test_transaction_observer_handles_status_changes()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status' => 'pending'
        ]);

        Log::shouldReceive('info')->once()->with('Transaction being updated', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction updated successfully', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction completed', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction completion notification sent', \Mockery::type('array'));

        $transaction->update(['status' => 'completed']);

        // Verify processed_at was set
        $this->assertNotNull($transaction->fresh()->processed_at);
    }

    public function test_transaction_observer_prevents_deletion_of_completed_transactions()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status' => 'completed'
        ]);

        Log::shouldReceive('warning')->once()->with('Transaction being deleted', \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete completed transactions');

        $transaction->delete();
    }

    public function test_transaction_observer_allows_deletion_of_pending_transactions()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status' => 'pending'
        ]);

        Log::shouldReceive('warning')->once()->with('Transaction being deleted', \Mockery::type('array'));
        Log::shouldReceive('warning')->once()->with('Transaction deleted successfully', \Mockery::type('array'));

        $transaction->delete();

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id
        ]);
    }

    public function test_transaction_observer_logs_failed_transactions()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status' => 'pending'
        ]);

        Log::shouldReceive('info')->once()->with('Transaction being updated', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction updated successfully', \Mockery::type('array'));
        Log::shouldReceive('warning')->once()->with('Transaction failed', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction failure notification sent', \Mockery::type('array'));

        $transaction->update(['status' => 'failed']);
    }

    public function test_transaction_observer_processes_pending_transactions()
    {
        $wallet = Wallet::factory()->create();

        Log::shouldReceive('info')->once()->with('Transaction being created', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Transaction created successfully', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Processing pending transaction', \Mockery::type('array'));

        Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status' => 'pending'
        ]);
    }
}
