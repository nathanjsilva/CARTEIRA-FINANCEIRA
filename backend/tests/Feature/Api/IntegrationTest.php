<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_user_workflow()
    {
        // 1. Register a new user
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $registerResponse = $this->postJson('/api/auth/register', $userData);
        $registerResponse->assertStatus(201);
        
        $token = $registerResponse->json('data.token');
        $walletId = $registerResponse->json('data.wallet.id');

        // 2. Login with the user
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'password123'
        ]);
        $loginResponse->assertStatus(200);

        // 3. Get user profile
        $profileResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/auth/me');
        $profileResponse->assertStatus(200);

        // 4. Check initial balance
        $balanceResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/wallet/balance');
        $balanceResponse->assertStatus(200);
        $this->assertEquals(0.00, $balanceResponse->json('data.balance'));

        // 5. Make a deposit
        $depositResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/wallet/deposit', [
            'amount' => 1000.00,
            'description' => 'Depósito inicial'
        ]);
        $depositResponse->assertStatus(201);
        $this->assertEquals(1000.00, $depositResponse->json('data.new_balance'));

        // 6. Create another user for transfer
        $user2 = User::factory()->create();
        $wallet2 = Wallet::factory()->create([
            'user_id' => $user2->id,
            'balance' => 500.00
        ]);

        // 7. Transfer money to second user
        $transferResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/wallet/transfer', [
            'to_wallet_id' => $wallet2->id,
            'amount' => 300.00,
            'description' => 'Transferência para amigo'
        ]);
        $transferResponse->assertStatus(201);

        // 8. Check balances after transfer
        $balanceAfterTransfer = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/wallet/balance');
        $this->assertEquals(700.00, $balanceAfterTransfer->json('data.balance'));

        // 9. Get transaction history
        $historyResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/wallet/history');
        $historyResponse->assertStatus(200);
        $this->assertCount(2, $historyResponse->json('data.transactions')); // deposit + transfer

        // 10. Make a withdrawal
        $withdrawResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/wallet/withdraw', [
            'amount' => 100.00,
            'description' => 'Saque para pagamento'
        ]);
        $withdrawResponse->assertStatus(201);
        $this->assertEquals(600.00, $withdrawResponse->json('data.new_balance'));

        // 11. Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');
        $logoutResponse->assertStatus(200);

        // 12. Verify token is revoked
        $unauthorizedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/auth/me');
        $unauthorizedResponse->assertStatus(401);
    }

    public function test_admin_reversal_workflow()
    {
        // 1. Create admin user
        $admin = User::factory()->create(['is_admin' => true]);
        $adminToken = $admin->createToken('test-token')->plainTextToken;

        // 2. Create regular user with transaction
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00
        ]);

        // 3. Create a transaction
        $transaction = \App\Models\Transaction::factory()->transfer()->completed()->create([
            'from_wallet_id' => $wallet->id,
            'amount' => 200.00,
            'description' => 'Transferência para testar reversão'
        ]);

        // 4. User requests reversal
        $userToken = $user->createToken('user-token')->plainTextToken;
        $reversalRequest = $this->withHeaders([
            'Authorization' => 'Bearer ' . $userToken
        ])->postJson('/api/transactions/reversal/request', [
            'transaction_id' => $transaction->uuid,
            'reason' => 'user_request',
            'description' => 'Erro no valor da transação'
        ]);
        $reversalRequest->assertStatus(201);

        $reversalId = $reversalRequest->json('data.reversal_id');

        // 5. Admin views pending reversals
        $pendingReversals = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken
        ])->getJson('/api/transactions/reversals/pending');
        $pendingReversals->assertStatus(200);
        $this->assertCount(1, $pendingReversals->json('data.reversals'));

        // 6. Admin approves reversal
        $approveReversal = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken
        ])->postJson("/api/transactions/reversal/{$reversalId}/approve");
        $approveReversal->assertStatus(200);

        // 7. Admin views reversal history
        $reversalHistory = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken
        ])->getJson('/api/transactions/reversals/history');
        $reversalHistory->assertStatus(200);
        $this->assertCount(1, $reversalHistory->json('data.reversals'));
    }

    public function test_error_handling_workflow()
    {
        $user = $this->actingAsUserWithWallet(['balance' => 100.00]);

        // Test insufficient funds for withdrawal
        $withdrawResponse = $this->postJson('/api/wallet/withdraw', [
            'amount' => 500.00,
            'description' => 'Tentativa de saque maior que saldo'
        ]);
        $withdrawResponse->assertStatus(400);

        // Test transfer to same wallet
        $wallet = $user->getDefaultWallet();
        $transferResponse = $this->postJson('/api/wallet/transfer', [
            'to_wallet_id' => $wallet->id,
            'amount' => 50.00,
            'description' => 'Transferência para mesma carteira'
        ]);
        $transferResponse->assertStatus(400);

        // Test invalid amount
        $depositResponse = $this->postJson('/api/wallet/deposit', [
            'amount' => -100.00,
            'description' => 'Depósito com valor negativo'
        ]);
        $depositResponse->assertStatus(422);
    }
}
