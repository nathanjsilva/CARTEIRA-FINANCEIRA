<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\TransactionReversal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_transfer_to_another_user(): void
    {
        $sender   = $this->actingAsUserWithWallet([], ['balance' => 1000.00]);
        $recipient = User::factory()->create();
        Wallet::factory()->create(['user_id' => $recipient->id, 'balance' => 0]);

        $response = $this->postJson('/api/v1/transactions/transfer', [
            'recipient_id' => $recipient->id,
            'amount'       => 200.00,
            'description'  => 'Pagamento',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Transferência realizada com sucesso']);

        $this->assertEquals(800.00, (float) $sender->getDefaultWallet()->fresh()->balance);
        $this->assertEquals(200.00, (float) $recipient->getDefaultWallet()->fresh()->balance);
    }

    public function test_transfer_fails_with_insufficient_balance(): void
    {
        $recipient = User::factory()->create();
        Wallet::factory()->create(['user_id' => $recipient->id]);

        $this->actingAsUserWithWallet([], ['balance' => 50.00]);

        $this->postJson('/api/v1/transactions/transfer', [
            'recipient_id' => $recipient->id,
            'amount'       => 500.00,
        ])->assertStatus(422);
    }

    public function test_transfer_fails_with_nonexistent_recipient(): void
    {
        $this->actingAsUserWithWallet([], ['balance' => 500.00]);

        $this->postJson('/api/v1/transactions/transfer', [
            'recipient_id' => 99999,
            'amount'       => 100.00,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_id']);
    }

    public function test_user_can_request_reversal(): void
    {
        $user   = $this->actingAsUserWithWallet([], ['balance' => 1000.00]);
        $wallet = $user->getDefaultWallet();

        $recipient = User::factory()->create();
        $toWallet  = Wallet::factory()->create(['user_id' => $recipient->id, 'balance' => 0]);

        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id,
            'to_wallet_id'   => $toWallet->id,
            'amount'         => 100.00,
        ]);

        $response = $this->postJson('/api/v1/transactions/reversal/request', [
            'transaction_id' => $transaction->uuid,
            'reason'         => 'user_request',
            'description'    => 'Erro no valor',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['reversal_id', 'original_transaction_id', 'status', 'reason']]);

        $this->assertDatabaseHas('transaction_reversals', [
            'original_transaction_id' => $transaction->id,
            'requested_by'            => $user->id,
            'status'                  => 'pending',
        ]);
    }

    public function test_reversal_of_own_pending_transaction_rejected(): void
    {
        $user   = $this->actingAsUserWithWallet([], ['balance' => 500.00]);
        $wallet = $user->getDefaultWallet();

        $transaction = Transaction::factory()->transfer()->pending()->create([
            'from_wallet_id' => $wallet->id,
        ]);

        $this->postJson('/api/v1/transactions/reversal/request', [
            'transaction_id' => $transaction->uuid,
            'reason'         => 'user_request',
        ])->assertStatus(422);
    }

    public function test_reversal_request_fails_with_invalid_reason(): void
    {
        $user   = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id,
        ]);

        $this->postJson('/api/v1/transactions/reversal/request', [
            'transaction_id' => $transaction->uuid,
            'reason'         => 'motivo_invalido',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_user_cannot_request_reversal_for_other_users_transaction(): void
    {
        $this->actingAsUserWithWallet();

        $other       = User::factory()->create();
        $otherWallet = Wallet::factory()->create(['user_id' => $other->id, 'balance' => 500]);
        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $otherWallet->id,
        ]);

        $this->postJson('/api/v1/transactions/reversal/request', [
            'transaction_id' => $transaction->uuid,
            'reason'         => 'user_request',
        ])->assertStatus(403);
    }

    public function test_user_can_approve_reversal(): void
    {
        $user    = $this->actingAsUserWithWallet([], ['balance' => 0]);
        $wallet  = $user->getDefaultWallet();

        $other      = User::factory()->create();
        $otherWallet = Wallet::factory()->create(['user_id' => $other->id, 'balance' => 200]);

        $original = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id,
            'to_wallet_id'   => $otherWallet->id,
            'amount'         => 200.00,
        ]);

        $reversalTx = Transaction::factory()->reversal()->pending()->create([
            'from_wallet_id' => $otherWallet->id,
            'to_wallet_id'   => $wallet->id,
            'amount'         => 200.00,
            'reference_id'   => $original->uuid,
        ]);

        $reversal = TransactionReversal::factory()->pending()->create([
            'original_transaction_id' => $original->id,
            'reversal_transaction_id' => $reversalTx->id,
            'requested_by'            => $user->id,
        ]);

        $response = $this->postJson("/api/v1/transactions/reversal/{$reversal->uuid}/approve");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Reversão aprovada e executada com sucesso']);
    }

    public function test_user_can_reject_reversal(): void
    {
        $user   = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        $original = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id,
        ]);

        $reversalTx = Transaction::factory()->reversal()->pending()->create([
            'from_wallet_id' => $wallet->id,
            'amount'         => 100.00,
        ]);

        $reversal = TransactionReversal::factory()->pending()->create([
            'original_transaction_id' => $original->id,
            'reversal_transaction_id' => $reversalTx->id,
            'requested_by'            => $user->id,
        ]);

        $this->postJson("/api/v1/transactions/reversal/{$reversal->uuid}/reject")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Reversão rejeitada com sucesso']);

        $this->assertDatabaseHas('transaction_reversals', ['id' => $reversal->id, 'status' => 'rejected']);
    }

    public function test_unauthenticated_user_cannot_transfer(): void
    {
        $this->postJson('/api/v1/transactions/transfer', ['recipient_id' => 1, 'amount' => 100])
            ->assertStatus(401);
    }
}
