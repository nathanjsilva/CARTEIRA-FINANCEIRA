<?php

namespace Tests\Unit\Application\Services\Auth;

use App\Application\DTOs\Auth\RegisterRequestDTO;
use App\Application\Services\Auth\RegisterUserService;
use App\Domain\Entities\User as UserEntity;
use App\Domain\Repositories\UserRepository;
use App\Models\User as UserModel;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterUserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_dto(): void
    {
        $service = app(RegisterUserService::class);

        $dto = new RegisterRequestDTO(
            name:     'João Silva',
            email:    'joao@example.com',
            password: 'password123',
        );

        $result = $service->execute($dto);

        $this->assertEquals('João Silva', $result->name);
        $this->assertEquals('joao@example.com', $result->email);
        $this->assertNotEmpty($result->token);
        $this->assertEquals(0.0, $result->balance);
        $this->assertEquals('BRL', $result->currency);

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
        $this->assertDatabaseHas('wallets', ['currency' => 'BRL', 'balance' => 0]);
    }

    public function test_register_creates_wallet_for_user(): void
    {
        $service = app(RegisterUserService::class);

        $service->execute(new RegisterRequestDTO('Maria', 'maria@test.com', 'senha1234'));

        $user   = UserModel::where('email', 'maria@test.com')->first();
        $wallet = $user->wallets()->first();

        $this->assertNotNull($wallet);
        $this->assertEquals('BRL', $wallet->currency);
        $this->assertTrue($wallet->is_active);
    }
}
