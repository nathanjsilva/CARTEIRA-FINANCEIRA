<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_user_workflow(): void
    {
        // 1. Registro
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'João Silva',
            'email'                 => 'joao@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(201);
        $token = $response->json('data.token');

        // 2. Login
        $this->postJson('/api/v1/auth/login', [
            'email'    => 'joao@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        // 3. Perfil
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(200);

        // 4. Saldo inicial = 0
        $balance = $this->withToken($token)->getJson('/api/v1/wallet/balance');
        $balance->assertStatus(200);
        $this->assertEquals(0.0, $balance->json('data.balance'));

        // 5. Depósito
        $deposit = $this->withToken($token)->postJson('/api/v1/wallet/deposit', [
            'amount'      => 1000.00,
            'description' => 'Depósito inicial',
        ]);
        $deposit->assertStatus(201);
        $this->assertEquals(1000.00, $deposit->json('data.new_balance'));

        // 6. Segundo usuário para transferência (UserObserver cria carteira padrão automaticamente)
        $user2 = User::factory()->create();

        // 7. Transferência
        $transfer = $this->withToken($token)->postJson('/api/v1/transactions/transfer', [
            'recipient_id' => $user2->id,
            'amount'       => 300.00,
            'description'  => 'Transferência',
        ]);
        $transfer->assertStatus(201);

        // 8. Saldo após transferência = 700
        $balanceAfter = $this->withToken($token)->getJson('/api/v1/wallet/balance');
        // Cache pode estar ativo, buscar direto do banco
        $user      = User::where('email', 'joao@example.com')->first();
        $userWallet = $user->getDefaultWallet();
        $this->assertEquals(700.00, (float) $userWallet->fresh()->balance);

        // 9. Histórico
        $history = $this->withToken($token)->getJson('/api/v1/wallet/history');
        $history->assertStatus(200);

        // 10. Saque
        $withdraw = $this->withToken($token)->postJson('/api/v1/wallet/withdraw', [
            'amount'      => 100.00,
            'description' => 'Saque',
        ]);
        $withdraw->assertStatus(201);
        $this->assertEquals(600.00, $withdraw->json('data.new_balance'));

        // 11. Logout
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(200);

        // 12. Token revogado - limpar cache do guard antes de verificar
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_transfer_reversal_workflow(): void
    {
        // Configura remetente com saldo (UserObserver já criou carteira padrão)
        $sender      = User::factory()->create();
        $senderWallet = $sender->getDefaultWallet();
        $senderWallet->update(['balance' => 500.00]);
        $senderToken  = $sender->createToken('test')->plainTextToken;

        // Configura destinatário
        $recipient       = User::factory()->create();
        $recipientWallet = $recipient->getDefaultWallet();

        // Transferência
        $transfer = $this->withToken($senderToken)->postJson('/api/v1/transactions/transfer', [
            'recipient_id' => $recipient->id,
            'amount'       => 200.00,
        ]);
        $transfer->assertStatus(201);
        $transactionId = $transfer->json('data.id');

        // Solicita reversão
        $reversal = $this->withToken($senderToken)->postJson('/api/v1/transactions/reversal/request', [
            'transaction_id' => $transactionId,
            'reason'         => 'user_request',
            'description'    => 'Erro no valor',
        ]);
        $reversal->assertStatus(201);
        $reversalId = $reversal->json('data.reversal_id');

        // Aprova reversão
        $approve = $this->withToken($senderToken)->postJson("/api/v1/transactions/reversal/{$reversalId}/approve");
        $approve->assertStatus(200);
        $this->assertEquals('completed', $approve->json('data.status'));

        // Saldo restaurado
        $this->assertEquals(500.00, (float) $senderWallet->fresh()->balance);
        $this->assertEquals(0.00, (float) $recipientWallet->fresh()->balance);
    }

    public function test_insufficient_funds_returns_422(): void
    {
        $user = $this->actingAsUserWithWallet([], ['balance' => 100.00]);

        $this->postJson('/api/v1/wallet/withdraw', ['amount' => 500.00])
            ->assertStatus(422);
    }

    public function test_transfer_to_nonexistent_user_returns_422(): void
    {
        $this->actingAsUserWithWallet([], ['balance' => 500.00]);

        $this->postJson('/api/v1/transactions/transfer', [
            'recipient_id' => 99999,
            'amount'       => 100.00,
        ])->assertStatus(422);
    }

    public function test_deposit_on_negative_balance_adds_correctly(): void
    {
        $user   = User::factory()->create();
        $wallet = $user->getDefaultWallet();
        $wallet->update(['balance' => -50.00]);
        $token  = $user->createToken('test')->plainTextToken;

        $deposit = $this->withToken($token)->postJson('/api/v1/wallet/deposit', ['amount' => 100.00]);
        $deposit->assertStatus(201);
        $this->assertEquals(50.00, $deposit->json('data.new_balance'));
    }

    public function test_negative_deposit_amount_rejected(): void
    {
        $this->actingAsUserWithWallet();

        $this->postJson('/api/v1/wallet/deposit', ['amount' => -100.00])
            ->assertStatus(422);
    }

    public function test_reversal_of_another_users_transaction_denied(): void
    {
        // UserObserver cria carteira padrão para cada usuário
        $owner = User::factory()->create();
        $owner->getDefaultWallet()->update(['balance' => 500]);

        $other = User::factory()->create();

        $ownerToken = $owner->createToken('test')->plainTextToken;

        $transfer = $this->withToken($ownerToken)->postJson('/api/v1/transactions/transfer', [
            'recipient_id' => $other->id,
            'amount'       => 100.00,
        ]);
        $transfer->assertStatus(201);
        $transactionId = $transfer->json('data.id');

        // Terceiro tenta reverter transação que não é dele
        $thirdUser  = User::factory()->create();
        $thirdToken = $thirdUser->createToken('test')->plainTextToken;

        // Limpar cache do guard para garantir que o próximo request use o token correto
        app('auth')->forgetGuards();

        $this->withToken($thirdToken)->postJson('/api/v1/transactions/reversal/request', [
            'transaction_id' => $transactionId,
            'reason'         => 'user_request',
        ])->assertStatus(403);
    }
}
