<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_wallet_balance(): void
    {
        $this->actingAsUserWithWallet([], ['balance' => 1500.75]);

        $response = $this->getJson('/api/v1/wallet/balance');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.balance', 1500.75)
            ->assertJsonPath('data.currency', 'BRL');
    }

    public function test_balance_exposes_uuid_not_id(): void
    {
        $user   = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        $response = $this->getJson('/api/v1/wallet/balance');

        $response->assertStatus(200)
            ->assertJsonPath('data.wallet_id', $wallet->uuid);
    }

    public function test_inactive_wallet_returns_404(): void
    {
        $user   = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        // Desativar carteira e deletar para simular ausência
        $wallet->update(['balance' => 0]);
        $wallet->delete();

        $this->getJson('/api/v1/wallet/balance')
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_user_can_deposit_money(): void
    {
        $user = $this->actingAsUserWithWallet([], ['balance' => 500.00]);

        $response = $this->postJson('/api/v1/wallet/deposit', [
            'amount'      => 250.50,
            'description' => 'Depósito via PIX',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Depósito realizado com sucesso'])
            ->assertJsonPath('data.new_balance', 750.50);

        $this->assertEquals(750.50, (float) $user->getDefaultWallet()->fresh()->balance);
    }

    public function test_deposit_fails_with_negative_amount(): void
    {
        $this->actingAsUserWithWallet();

        $this->postJson('/api/v1/wallet/deposit', ['amount' => -100.00])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_deposit_fails_with_zero_amount(): void
    {
        $this->actingAsUserWithWallet();

        $this->postJson('/api/v1/wallet/deposit', ['amount' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_user_can_withdraw_money(): void
    {
        $user = $this->actingAsUserWithWallet([], ['balance' => 1000.00]);

        $response = $this->postJson('/api/v1/wallet/withdraw', [
            'amount'      => 300.75,
            'description' => 'Saque',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Saque realizado com sucesso']);

        $this->assertEquals(699.25, (float) $user->getDefaultWallet()->fresh()->balance);
    }

    public function test_withdraw_fails_with_insufficient_balance(): void
    {
        $this->actingAsUserWithWallet([], ['balance' => 100.00]);

        $this->postJson('/api/v1/wallet/withdraw', ['amount' => 500.00])
            ->assertStatus(422);
    }

    public function test_user_can_get_transaction_history(): void
    {
        $user   = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        Transaction::factory()->deposit()->completed()->create(['to_wallet_id' => $wallet->id, 'amount' => 100.00]);
        Transaction::factory()->withdrawal()->completed()->create(['from_wallet_id' => $wallet->id, 'amount' => 50.00]);

        $response = $this->getJson('/api/v1/wallet/history');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transactions' => [
                        '*' => ['id', 'type', 'amount', 'status', 'created_at'],
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data.transactions'));
    }

    public function test_history_limit_param_is_respected(): void
    {
        $user   = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        Transaction::factory()->count(10)->deposit()->completed()->create(['to_wallet_id' => $wallet->id]);

        $response = $this->getJson('/api/v1/wallet/history?limit=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data.transactions'));
    }

    public function test_unauthenticated_user_cannot_access_wallet_endpoints(): void
    {
        foreach ([
            ['GET',  '/api/v1/wallet/balance'],
            ['POST', '/api/v1/wallet/deposit'],
            ['POST', '/api/v1/wallet/withdraw'],
            ['GET',  '/api/v1/wallet/history'],
        ] as [$method, $path]) {
            $this->json($method, $path)->assertStatus(401);
        }
    }
}
