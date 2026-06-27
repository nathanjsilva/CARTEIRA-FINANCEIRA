<?php

namespace Tests\Unit\Observers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_update_to_positive_persists(): void
    {
        $user   = User::factory()->create();
        $wallet = $user->getDefaultWallet();

        $wallet->update(['balance' => 750.00]);

        $this->assertEquals(750.00, (float) $wallet->fresh()->balance);
    }

    public function test_balance_update_to_negative_is_allowed(): void
    {
        $user   = User::factory()->create();
        $wallet = $user->getDefaultWallet();
        $wallet->update(['balance' => 100.00]);

        $wallet->update(['balance' => -50.00]);

        $this->assertEquals(-50.00, (float) $wallet->fresh()->balance);
    }

    public function test_cannot_delete_wallet_with_balance(): void
    {
        $user   = User::factory()->create();
        $wallet = $user->getDefaultWallet();
        $wallet->update(['balance' => 100.00]);

        $this->expectException(\Exception::class);

        $wallet->delete();
    }

    public function test_cannot_delete_wallet_with_transactions(): void
    {
        $user   = User::factory()->create();
        $wallet = $user->getDefaultWallet();

        Transaction::factory()->create(['from_wallet_id' => $wallet->id]);

        $this->expectException(\Exception::class);

        $wallet->delete();
    }
}
