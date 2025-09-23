<?php

namespace Tests\Unit\Observers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_observer_creates_default_wallet_on_creation()
    {
        Log::shouldReceive('info')->once()->with('User being created', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('User created successfully', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('Default wallet created for user', \Mockery::type('array'));

        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);

        // Verify default wallet was created
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0.00,
            'currency' => 'BRL',
            'is_active' => true
        ]);

        // Verify user has default wallet
        $defaultWallet = $user->getDefaultWallet();
        $this->assertNotNull($defaultWallet);
        $this->assertEquals('BRL', $defaultWallet->currency);
    }

    public function test_user_observer_logs_updates()
    {
        $user = User::factory()->create();
        
        Log::shouldReceive('info')->once()->with('User being updated', \Mockery::type('array'));
        Log::shouldReceive('info')->once()->with('User updated successfully', \Mockery::type('array'));

        $user->update(['name' => 'João Silva Updated']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'João Silva Updated'
        ]);
    }

    public function test_user_observer_logs_deletion()
    {
        $user = User::factory()->create();
        
        Log::shouldReceive('warning')->once()->with('User being deleted', \Mockery::type('array'));
        Log::shouldReceive('warning')->once()->with('User deleted successfully', \Mockery::type('array'));

        $user->delete();

        $this->assertSoftDeleted('users', [
            'id' => $user->id
        ]);
    }
}
