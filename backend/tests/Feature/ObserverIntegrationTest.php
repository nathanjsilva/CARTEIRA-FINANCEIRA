<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Domain\Exceptions\InvalidTransactionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ObserverIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_triggers_default_wallet(): void
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
    }

    public function test_transaction_created_with_default_status_and_currency(): void
    {
        $wallet = Wallet::factory()->create();

        $transaction = Transaction::factory()->deposit()->create([
            'to_wallet_id' => $wallet->id,
            'amount'       => 100.00,
        ]);

        $this->assertEquals('BRL', $transaction->currency);
        $this->assertNotNull($transaction->status);
    }

    public function test_invalid_transaction_status_transition_throws(): void
    {
        $wallet      = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create(['from_wallet_id' => $wallet->id]);

        $this->expectException(InvalidTransactionException::class);

        $transaction->update(['status' => 'pending']);
    }

    public function test_valid_transaction_status_transition_succeeds(): void
    {
        $wallet      = Wallet::factory()->create();
        $transaction = Transaction::factory()->completed()->create(['from_wallet_id' => $wallet->id]);

        $transaction->update(['status' => 'reversed']);

        $this->assertEquals('reversed', $transaction->fresh()->status);
    }

    public function test_wallet_balance_update_allows_negative(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100.00]);

        $wallet->update(['balance' => -50.00]);

        $this->assertEquals(-50.00, (float) $wallet->fresh()->balance);
    }

    public function test_cannot_delete_wallet_with_balance(): void
    {
        $user   = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 100.00]);

        $this->expectException(\Exception::class);

        $wallet->delete();
    }

    public function test_reversal_approve_sets_approved_at(): void
    {
        $user     = User::factory()->create();
        $reversal = TransactionReversal::factory()->pending()->create(['requested_by' => $user->id]);

        $reversal->approve($user);

        $this->assertNotNull($reversal->fresh()->approved_at);
        $this->assertEquals('approved', $reversal->fresh()->status);
    }

    public function test_completed_transaction_cannot_be_deleted(): void
    {
        $transaction = Transaction::factory()->completed()->create();

        $this->expectException(InvalidTransactionException::class);

        $transaction->delete();
    }
}
