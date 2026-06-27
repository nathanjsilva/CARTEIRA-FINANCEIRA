<?php

namespace Tests\Unit\Observers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WalletObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_observer_logs_creation()
    {
        $user = User::factory()->create();
        
        Log::shouldReceive('info')->once()->with('Wallet being created', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Wallet created successfully', \Mockery::type('array'));

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 1000.00
        ]);
    }

    public function test_wallet_observer_validates_balance_changes()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 500.00
        ]);

        Log::shouldReceive('info')->once()->with('Wallet being updated', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Wallet updated successfully', \Mockery::type('array'));

        $wallet->update(['balance' => 750.00]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 750.00
        ]);
    }

    public function test_wallet_observer_prevents_negative_balance()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00
        ]);

        Log::shouldReceive('info')->once()->with('Wallet being updated', \Mockery::type('array'));
        Log::shouldReceive('error')->once()->with('Attempted negative balance', \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet balance cannot be negative');

        $wallet->update(['balance' => -50.00]);
    }

    public function test_wallet_observer_logs_large_balance_changes()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00
        ]);

        Log::shouldReceive('info')->once()->with('Wallet being updated', \Mockery::type('array'));
        Log::shouldReceive('warning')->once()->with('Large balance change detected', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Wallet updated successfully', \Mockery::type('array'));

        $wallet->update(['balance' => 15000.00]);
    }

    public function test_wallet_observer_prevents_deletion_with_balance()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00
        ]);

        Log::shouldReceive('warning')->once()->with('Wallet being deleted', \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete wallet with remaining balance');

        $wallet->delete();
    }

    public function test_wallet_observer_prevents_deletion_with_transactions()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00
        ]);

        // Create a transaction
        \App\Models\Transaction::factory()->create([
            'from_wallet_id' => $wallet->id
        ]);

        Log::shouldReceive('warning')->once()->with('Wallet being deleted', \Mockery::type('array'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete wallet with transaction history');

        $wallet->delete();
    }

    public function test_wallet_observer_clears_cache_on_update()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id
        ]);

        // Mock cache
        Cache::shouldReceive('forget')->once()->with("user_{$user->id}_wallets");
        Cache::shouldReceive('forget')->once()->with("user_{$user->id}_default_wallet");

        Log::shouldReceive('info')->once()->with('Wallet being updated', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Wallet updated successfully', \Mockery::type('array'));

        $wallet->update(['balance' => 1000.00]);
    }
}
