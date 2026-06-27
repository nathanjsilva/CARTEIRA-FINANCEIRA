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

    public function test_user_can_get_wallet_balance()
    {
        $user = $this->actingAsUserWithWallet(['balance' => 1500.75]);

        $response = $this->getJson('/api/wallet/balance');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'wallet_id' => $user->getDefaultWallet()->id,
                    'balance' => 1500.75,
                    'currency' => 'BRL',
                    'is_active' => true
                ]
            ]);
    }

    public function test_user_without_wallet_cannot_get_balance()
    {
        $user = $this->actingAsUser();

        $response = $this->getJson('/api/wallet/balance');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Carteira não encontrada'
            ]);
    }

    public function test_user_can_deposit_money()
    {
        $user = $this->actingAsUserWithWallet(['balance' => 500.00]);

        $depositData = [
            'amount' => 250.50,
            'description' => 'Depósito via PIX'
        ];

        $response = $this->postJson('/api/wallet/deposit', $depositData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Depósito realizado com sucesso'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'transaction_id',
                    'amount',
                    'currency',
                    'status',
                    'new_balance'
                ]
            ]);

        $this->assertEquals(750.50, $user->getDefaultWallet()->fresh()->balance);
        
        $this->assertDatabaseHas('transactions', [
            'from_wallet_id' => null,
            'to_wallet_id' => $user->getDefaultWallet()->id,
            'type' => 'deposit',
            'amount' => 250.50,
            'status' => 'completed'
        ]);
    }

    public function test_deposit_fails_with_invalid_amount()
    {
        $user = $this->actingAsUserWithWallet();

        $depositData = [
            'amount' => -100.00,
            'description' => 'Depósito inválido'
        ];

        $response = $this->postJson('/api/wallet/deposit', $depositData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_user_can_withdraw_money()
    {
        $user = $this->actingAsUserWithWallet(['balance' => 1000.00]);

        $withdrawData = [
            'amount' => 300.75,
            'description' => 'Saque para pagamento'
        ];

        $response = $this->postJson('/api/wallet/withdraw', $withdrawData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Saque realizado com sucesso'
            ]);

        $this->assertEquals(699.25, $user->getDefaultWallet()->fresh()->balance);
        
        $this->assertDatabaseHas('transactions', [
            'from_wallet_id' => $user->getDefaultWallet()->id,
            'to_wallet_id' => null,
            'type' => 'withdrawal',
            'amount' => 300.75,
            'status' => 'completed'
        ]);
    }

    public function test_withdraw_fails_with_insufficient_balance()
    {
        $user = $this->actingAsUserWithWallet(['balance' => 100.00]);

        $withdrawData = [
            'amount' => 500.00,
            'description' => 'Saque maior que saldo'
        ];

        $response = $this->postJson('/api/wallet/withdraw', $withdrawData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Falha no saque'
            ]);
    }

    public function test_user_can_transfer_money_to_another_wallet()
    {
        $fromUser = $this->actingAsUserWithWallet(['balance' => 1000.00]);
        $toUser = User::factory()->create();
        $toWallet = Wallet::factory()->create([
            'user_id' => $toUser->id,
            'balance' => 500.00
        ]);

        $transferData = [
            'to_wallet_id' => $toWallet->id,
            'amount' => 200.00,
            'description' => 'Transferência para amigo'
        ];

        $response = $this->postJson('/api/wallet/transfer', $transferData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Transferência realizada com sucesso'
            ]);

        $this->assertEquals(800.00, $fromUser->getDefaultWallet()->fresh()->balance);
        $this->assertEquals(700.00, $toWallet->fresh()->balance);
        
        $this->assertDatabaseHas('transactions', [
            'from_wallet_id' => $fromUser->getDefaultWallet()->id,
            'to_wallet_id' => $toWallet->id,
            'type' => 'transfer',
            'amount' => 200.00,
            'status' => 'completed'
        ]);
    }

    public function test_transfer_fails_with_invalid_wallet_id()
    {
        $user = $this->actingAsUserWithWallet(['balance' => 1000.00]);

        $transferData = [
            'to_wallet_id' => 99999,
            'amount' => 100.00,
            'description' => 'Transferência para carteira inexistente'
        ];

        $response = $this->postJson('/api/wallet/transfer', $transferData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_wallet_id']);
    }

    public function test_transfer_fails_with_insufficient_balance()
    {
        $fromUser = $this->actingAsUserWithWallet(['balance' => 100.00]);
        $toUser = User::factory()->create();
        $toWallet = Wallet::factory()->create(['user_id' => $toUser->id]);

        $transferData = [
            'to_wallet_id' => $toWallet->id,
            'amount' => 500.00,
            'description' => 'Transferência maior que saldo'
        ];

        $response = $this->postJson('/api/wallet/transfer', $transferData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Falha na transferência'
            ]);
    }

    public function test_user_can_get_transaction_history()
    {
        $user = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        // Create some transactions
        Transaction::factory()->deposit()->completed()->create([
            'to_wallet_id' => $wallet->id,
            'amount' => 100.00
        ]);

        Transaction::factory()->withdrawal()->completed()->create([
            'from_wallet_id' => $wallet->id,
            'amount' => 50.00
        ]);

        $response = $this->getJson('/api/wallet/history');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transactions' => [
                        '*' => [
                            'id',
                            'type',
                            'amount',
                            'currency',
                            'status',
                            'description',
                            'created_at'
                        ]
                    ],
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('data.transactions'));
    }

    public function test_user_can_get_paginated_transaction_history()
    {
        $user = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        // Create 15 transactions
        Transaction::factory()->count(15)->deposit()->completed()->create([
            'to_wallet_id' => $wallet->id,
            'amount' => 100.00
        ]);

        $response = $this->getJson('/api/wallet/history?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertCount(10, $response->json('data.transactions'));
        $this->assertEquals(15, $response->json('data.pagination.total'));
        $this->assertEquals(1, $response->json('data.pagination.current_page'));
    }

    public function test_unauthenticated_user_cannot_access_wallet_endpoints()
    {
        $endpoints = [
            ['GET', '/api/wallet/balance'],
            ['POST', '/api/wallet/deposit', ['amount' => 100]],
            ['POST', '/api/wallet/withdraw', ['amount' => 100]],
            ['POST', '/api/wallet/transfer', ['to_wallet_id' => 1, 'amount' => 100]],
            ['GET', '/api/wallet/history']
        ];

        foreach ($endpoints as $endpoint) {
            [$method, $endpointPath, $data] = array_pad($endpoint, 3, []);
            $response = $this->json($method, $endpointPath, $data);
            $response->assertStatus(401);
        }
    }

    public function test_transfer_fails_when_transferring_to_same_wallet()
    {
        $user = $this->actingAsUserWithWallet(['balance' => 1000.00]);
        $wallet = $user->getDefaultWallet();

        $transferData = [
            'to_wallet_id' => $wallet->id,
            'amount' => 100.00,
            'description' => 'Transferência para mesma carteira'
        ];

        $response = $this->postJson('/api/wallet/transfer', $transferData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Falha na transferência'
            ]);
    }
}
