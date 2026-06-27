<?php

namespace Tests\Unit\Observers;

use App\Domain\Exceptions\InvalidTransactionException;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_sets_default_status_on_creation(): void
    {
        $wallet = Wallet::factory()->create();

        $transaction = Transaction::factory()->create([
            'from_wallet_id' => $wallet->id,
            'status'         => null,
            'currency'       => null,
        ]);

        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals('BRL', $transaction->currency);
    }

    public function test_invalid_status_transition_throws(): void
    {
        $wallet      = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create(['from_wallet_id' => $wallet->id]);

        $this->expectException(InvalidTransactionException::class);

        $transaction->update(['status' => 'pending']);
    }

    public function test_valid_status_transition_succeeds(): void
    {
        $wallet      = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create(['from_wallet_id' => $wallet->id]);

        $transaction->update(['status' => 'reversed']);

        $this->assertEquals('reversed', $transaction->fresh()->status);
    }

    public function test_cannot_delete_completed_transaction(): void
    {
        $transaction = Transaction::factory()->completed()->create();

        $this->expectException(InvalidTransactionException::class);

        $transaction->delete();
    }

    public function test_can_delete_pending_transaction(): void
    {
        $wallet      = Wallet::factory()->create();
        $transaction = Transaction::factory()->pending()->create(['from_wallet_id' => $wallet->id]);

        $transaction->delete();

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_same_status_update_skips_transition_check(): void
    {
        $wallet      = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create(['from_wallet_id' => $wallet->id]);

        // Updating a non-status field should not throw
        $transaction->update(['description' => 'Updated description']);

        $this->assertEquals('completed', $transaction->fresh()->status);
    }
}
