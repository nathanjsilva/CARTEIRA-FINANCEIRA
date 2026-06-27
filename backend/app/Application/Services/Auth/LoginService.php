<?php

namespace App\Application\Services\Auth;

use App\Application\DTOs\Auth\AuthResponseDTO;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Hash;

final class LoginService
{
    public function execute(string $email, string $password): AuthResponseDTO
    {
        $user = UserModel::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \DomainException('Credenciais inválidas');
        }

        $wallet = $user->getDefaultWallet();
        $token  = $user->createToken('auth_token')->plainTextToken;

        return new AuthResponseDTO(
            userId:   (string) $user->id,
            name:     $user->name,
            email:    $user->email,
            balance:  (float) ($wallet?->balance ?? 0),
            walletId: $wallet?->uuid,
            currency: $wallet?->currency ?? 'BRL',
            token:    $token,
        );
    }
}
