<?php

namespace Tests\Unit\Observers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_auto_creates_default_wallet(): void
    {
        $user = User::factory()->create([
            'name'  => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
        $this->assertDatabaseHas('wallets', [
            'user_id'   => $user->id,
            'balance'   => 0.00,
            'currency'  => 'BRL',
            'is_active' => true,
        ]);

        $wallet = $user->getDefaultWallet();
        $this->assertNotNull($wallet);
        $this->assertEquals('BRL', $wallet->currency);
        $this->assertEquals(0.00, (float) $wallet->balance);
    }

    public function test_user_update_persists(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $user->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }
}
