<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\TransactionReversal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ObserverIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_workflow_with_observers()
    {
        // Mock all log calls
        Log::shouldReceive('info')->withAnyArgs();
        Log::shouldReceive('warning')->withAnyArgs();

        // 1. Create user (should trigger UserObserver and create default wallet)
        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);

        // Verify default wallet was created by observer
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0.00,
            'currency' => 'BRL',
            'is_active' => true
        ]);

        // 2. Create a transaction (should trigger TransactionObserver)
        $wallet = $user->getDefaultWallet();
        $transaction = Transaction::factory()->deposit()->create([
            'to_wallet_id' => $wallet->id,
            'amount' => 1000.00,
            'status' => 'pending'
        ]);

        // Verify transaction was created with defaults
        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals('BRL', $transaction->currency);

        // 3. Update transaction to completed (should trigger status change logic)
        $transaction->update(['status' => 'completed']);

        // Verify processed_at was set by observer
        $this->assertNotNull($transaction->fresh()->processed_at);

        // 4. Update wallet balance (should trigger WalletObserver)
        $wallet->update(['balance' => 1000.00]);

        // Verify balance was updated
        $this->assertEquals(1000.00, $wallet->fresh()->balance);

        // 5. Create a reversal request (should trigger TransactionReversalObserver)
        $reversal = TransactionReversal::factory()->create([
            'original_transaction_id' => $transaction->id,
            'status' => 'pending'
        ]);

        // Verify reversal was created with default status
        $this->assertEquals('pending', $reversal->status);

        // 6. Approve reversal (should trigger approval logic)
        $reversal->update(['status' => 'approved']);

        // Verify approval timestamp was set
        $this->assertNotNull($reversal->fresh()->approved_at);
    }

    public function test_observer_prevents_invalid_operations()
    {
        Log::shouldReceive('info')->withAnyArgs();
        Log::shouldReceive('warning')->withAnyArgs();
        Log::shouldReceive('error')->withAnyArgs();

        // Test wallet with negative balance
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet balance cannot be negative');

        $wallet->update(['balance' => -50.00]);
    }

    public function test_observer_prevents_deletion_of_protected_records()
    {
        Log::shouldReceive('info')->withAnyArgs();
        Log::shouldReceive('warning')->withAnyArgs();

        // Test deletion of wallet with balance
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete wallet with remaining balance');

        $wallet->delete();
    }

    public function test_observer_handles_status_transitions_correctly()
    {
        Log::shouldReceive('info')->withAnyArgs();
        Log::shouldReceive('error')->withAnyArgs();

        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status' => 'completed'
        ]);

        // Test invalid status transition
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition from completed to pending');

        $transaction->update(['status' => 'pending']);
    }

    public function test_observers_log_all_operations()
    {
        // Test that observers are properly logging operations
        $logCalls = [];
        
        Log::shouldReceive('info')->andReturnUsing(function ($message, $context = []) use (&$logCalls) {
            $logCalls[] = $message;
            return true;
        });

        Log::shouldReceive('warning')->andReturnUsing(function ($message, $context = []) use (&$logCalls) {
            $logCalls[] = $message;
            return true;
        });

        // Create user
        $user = User::factory()->create();
        
        // Create wallet
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        
        // Create transaction
        Transaction::factory()->create(['to_wallet_id' => $wallet->id]);
        
        // Update wallet
        $wallet->update(['balance' => 500.00]);

        // Verify that observers logged the operations
        $this->assertContains('User being created', $logCalls);
        $this->assertContains('User created successfully', $logCalls);
        $this->assertContains('Default wallet created for user', $logCalls);
        $this->assertContains('Wallet being created', $logCalls);
        $this->assertContains('Wallet created successfully', $logCalls);
        $this->assertContains('Transaction being created', $logCalls);
        $this->assertContains('Transaction created successfully', $logCalls);
        $this->assertContains('Wallet being updated', $logCalls);
        $this->assertContains('Wallet updated successfully', $logCalls);
    }
}
