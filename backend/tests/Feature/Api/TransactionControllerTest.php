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

    public function test_user_can_request_transaction_reversal()
    {
        $user = $this->actingAsUserWithWallet(['balance' => 1000.00]);
        $wallet = $user->getDefaultWallet();

        // Create a completed transaction
        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id,
            'amount' => 100.00,
            'description' => 'Transferência para testar reversão'
        ]);

        $reversalData = [
            'transaction_id' => $transaction->uuid,
            'reason' => 'user_request',
            'description' => 'Erro no valor da transação'
        ];

        $response = $this->postJson('/api/transactions/reversal/request', $reversalData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Solicitação de reversão enviada com sucesso'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'reversal_id',
                    'original_transaction_id',
                    'status',
                    'reason'
                ]
            ]);

        $this->assertDatabaseHas('transaction_reversals', [
            'original_transaction_id' => $transaction->id,
            'requested_by' => $user->id,
            'reason' => 'user_request',
            'status' => 'pending'
        ]);
    }

    public function test_reversal_request_fails_with_invalid_reason()
    {
        $user = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);

        $reversalData = [
            'transaction_id' => $transaction->uuid,
            'reason' => 'invalid_reason',
            'description' => 'Razão inválida'
        ];

        $response = $this->postJson('/api/transactions/reversal/request', $reversalData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_reversal_request_fails_with_nonexistent_transaction()
    {
        $user = $this->actingAsUser();

        $reversalData = [
            'transaction_id' => 'nonexistent-uuid',
            'reason' => 'user_request',
            'description' => 'Transação inexistente'
        ];

        $response = $this->postJson('/api/transactions/reversal/request', $reversalData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['transaction_id']);
    }

    public function test_user_cannot_request_reversal_for_other_users_transactions()
    {
        $user = $this->actingAsUser();
        $otherUser = User::factory()->create();
        $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);

        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $otherWallet->id
        ]);

        $reversalData = [
            'transaction_id' => $transaction->uuid,
            'reason' => 'user_request',
            'description' => 'Tentativa de reversão de transação de outro usuário'
        ];

        $response = $this->postJson('/api/transactions/reversal/request', $reversalData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Você só pode solicitar reversão para suas próprias transações'
            ]);
    }

    public function test_admin_can_approve_reversal()
    {
        $admin = $this->actingAsUser(['is_admin' => true]);
        $user = User::factory()->create();
        $wallet = $user->getDefaultWallet();

        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id,
            'amount' => 100.00
        ]);

        $reversal = TransactionReversal::factory()->pending()->create([
            'original_transaction_id' => $transaction->id,
            'requested_by' => $user->id
        ]);

        $response = $this->postJson("/api/transactions/reversal/{$reversal->uuid}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reversão aprovada e executada com sucesso'
            ]);

        $this->assertDatabaseHas('transaction_reversals', [
            'id' => $reversal->id,
            'status' => 'approved',
            'approved_by' => $admin->id
        ]);
    }

    public function test_admin_can_reject_reversal()
    {
        $admin = $this->actingAsUser(['is_admin' => true]);
        $user = User::factory()->create();
        $wallet = $user->getDefaultWallet();

        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);

        $reversal = TransactionReversal::factory()->pending()->create([
            'original_transaction_id' => $transaction->id,
            'requested_by' => $user->id
        ]);

        $rejectData = [
            'reason' => 'Transação válida, sem necessidade de reversão'
        ];

        $response = $this->postJson("/api/transactions/reversal/{$reversal->uuid}/reject", $rejectData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reversão rejeitada com sucesso'
            ]);

        $this->assertDatabaseHas('transaction_reversals', [
            'id' => $reversal->id,
            'status' => 'rejected'
        ]);
    }

    public function test_admin_can_get_pending_reversals()
    {
        $admin = $this->actingAsUser(['is_admin' => true]);

        // Create some pending reversals
        TransactionReversal::factory()->count(3)->pending()->create();

        $response = $this->getJson('/api/transactions/reversals/pending');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'reversals' => [
                        '*' => [
                            'id',
                            'original_transaction_id',
                            'amount',
                            'currency',
                            'reason',
                            'description',
                            'requested_by',
                            'requested_at'
                        ]
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('data.reversals'));
    }

    public function test_admin_can_get_reversal_history()
    {
        $admin = $this->actingAsUser(['is_admin' => true]);

        // Create some reversals with different statuses
        TransactionReversal::factory()->approved()->create();
        TransactionReversal::factory()->rejected()->create();
        TransactionReversal::factory()->pending()->create();

        $response = $this->getJson('/api/transactions/reversals/history');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'reversals' => [
                        '*' => [
                            'id',
                            'original_transaction_id',
                            'amount',
                            'currency',
                            'reason',
                            'description',
                            'status',
                            'requested_at',
                            'approved_at',
                            'approved_by'
                        ]
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('data.reversals'));
    }

    public function test_non_admin_user_cannot_approve_reversal()
    {
        $user = $this->actingAsUser();
        $reversal = TransactionReversal::factory()->pending()->create();

        $response = $this->postJson("/api/transactions/reversal/{$reversal->uuid}/approve");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem aprovar reversões.'
            ]);
    }

    public function test_non_admin_user_cannot_reject_reversal()
    {
        $user = $this->actingAsUser();
        $reversal = TransactionReversal::factory()->pending()->create();

        $rejectData = ['reason' => 'Teste de rejeição'];

        $response = $this->postJson("/api/transactions/reversal/{$reversal->uuid}/reject", $rejectData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem rejeitar reversões.'
            ]);
    }

    public function test_non_admin_user_cannot_view_pending_reversals()
    {
        $user = $this->actingAsUser();

        $response = $this->getJson('/api/transactions/reversals/pending');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem visualizar reversões pendentes.'
            ]);
    }

    public function test_non_admin_user_cannot_view_reversal_history()
    {
        $user = $this->actingAsUser();

        $response = $this->getJson('/api/transactions/reversals/history');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem visualizar histórico de reversões.'
            ]);
    }

    public function test_unauthenticated_user_cannot_access_transaction_endpoints()
    {
        $endpoints = [
            ['POST', '/api/transactions/reversal/request', ['transaction_id' => 'test', 'reason' => 'user_request']],
            ['POST', '/api/transactions/reversal/test-uuid/approve'],
            ['POST', '/api/transactions/reversal/test-uuid/reject', ['reason' => 'test']],
            ['GET', '/api/transactions/reversals/pending'],
            ['GET', '/api/transactions/reversals/history']
        ];

        foreach ($endpoints as $endpoint) {
            [$method, $endpointPath, $data] = array_pad($endpoint, 3, []);
            $response = $this->json($method, $endpointPath, $data);
            $response->assertStatus(401);
        }
    }

    public function test_reversal_request_fails_for_pending_transaction()
    {
        $user = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();

        // Create a pending transaction
        $transaction = Transaction::factory()->transfer()->pending()->create([
            'from_wallet_id' => $wallet->id
        ]);

        $reversalData = [
            'transaction_id' => $transaction->uuid,
            'reason' => 'user_request',
            'description' => 'Tentativa de reversão de transação pendente'
        ];

        $response = $this->postJson('/api/transactions/reversal/request', $reversalData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Falha na solicitação de reversão'
            ]);
    }

    public function test_approve_reversal_fails_for_nonexistent_reversal()
    {
        $admin = $this->actingAsUser(['is_admin' => true]);

        $response = $this->postJson('/api/transactions/reversal/nonexistent-uuid/approve');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Reversão não encontrada'
            ]);
    }

    public function test_valid_reversal_reasons()
    {
        $user = $this->actingAsUserWithWallet();
        $wallet = $user->getDefaultWallet();
        $transaction = Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id
        ]);

        $validReasons = ['user_request', 'system_error', 'fraud_detection', 'compliance'];

        foreach ($validReasons as $reason) {
            $reversalData = [
                'transaction_id' => $transaction->uuid,
                'reason' => $reason,
                'description' => "Teste com razão: {$reason}"
            ];

            $response = $this->postJson('/api/transactions/reversal/request', $reversalData);
            $response->assertStatus(201);

            // Delete the created reversal to avoid conflicts
            TransactionReversal::latest()->first()->delete();
        }
    }
}
