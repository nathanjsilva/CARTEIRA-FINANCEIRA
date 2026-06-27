<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Usuário registrado com sucesso',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user'   => ['id', 'name', 'email'],
                    'wallet' => ['id', 'balance', 'currency'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => User::where('email', 'joao@example.com')->first()->id,
            'balance' => 0.00,
            'currency' => 'BRL',
            'is_active' => true
        ]);
    }

    public function test_user_registration_fails_with_invalid_email()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_registration_fails_with_password_mismatch()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password'
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'joao@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'joao@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login realizado com sucesso'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ],
                    'token'
                ]
            ]);
    }

    public function test_user_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'joao@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'joao@example.com',
            'password' => 'wrong_password'
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ]);
    }

    public function test_user_can_get_their_profile()
    {
        $user = $this->actingAsUser();

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ]
                ]
            ]);
    }

    public function test_unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout()
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ]);

        // Verify token was revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class
        ]);
    }

    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_user_registration_creates_default_wallet()
    {
        $userData = [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'maria@example.com')->first();
        $wallet = $user->wallets()->first();

        $this->assertNotNull($wallet);
        $this->assertEquals(0.00, $wallet->balance);
        $this->assertEquals('BRL', $wallet->currency);
        $this->assertTrue($wallet->is_active);
    }
}
