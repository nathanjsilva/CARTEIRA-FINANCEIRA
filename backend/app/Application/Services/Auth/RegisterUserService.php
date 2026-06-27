<?php

namespace App\Application\Services\Auth;

use App\Application\DTOs\Auth\RegisterRequestDTO;
use App\Application\DTOs\Auth\AuthResponseDTO;
use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class RegisterUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function execute(RegisterRequestDTO $dto): AuthResponseDTO
    {
        return DB::transaction(function () use ($dto) {
            $user = User::register(
                id:             (string) Str::uuid(),
                name:           $dto->name,
                email:          $dto->email,
                hashedPassword: Hash::make($dto->password),
            );

            $eloquentUser = $this->userRepository->create($user);

            $wallet = $eloquentUser->getDefaultWallet();
            $token  = $eloquentUser->createToken('auth_token')->plainTextToken;

            return new AuthResponseDTO(
                userId:   (string) $eloquentUser->id,
                name:     $eloquentUser->name,
                email:    $eloquentUser->email,
                balance:  (float) ($wallet?->balance ?? 0),
                walletId: $wallet?->uuid,
                currency: $wallet?->currency ?? 'BRL',
                token:    $token,
            );
        });
    }
}
