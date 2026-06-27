<?php

namespace App\Infrastructure\Http\Controllers\Api\V1;

use App\Application\DTOs\Auth\RegisterRequestDTO;
use App\Application\Services\Auth\RegisterUserService;
use App\Application\Services\Auth\LoginService;
use App\Presentation\Http\Requests\RegisterRequest;
use App\Presentation\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function __construct(
        private readonly RegisterUserService $registerService,
        private readonly LoginService $loginService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->registerService->execute(
                new RegisterRequestDTO(
                    name:     $request->validated('name'),
                    email:    $request->validated('email'),
                    password: $request->validated('password'),
                )
            );

            return response()->json(['success' => true, 'message' => 'Usuário registrado com sucesso', 'data' => $result->toArray()], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginService->execute(
                $request->validated('email'),
                $request->validated('password'),
            );

            return response()->json(['success' => true, 'message' => 'Login realizado com sucesso', 'data' => $result->toArray()]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Logout realizado com sucesso']);
    }

    public function me(Request $request): JsonResponse
    {
        $user   = $request->user();
        $wallet = $user->getDefaultWallet();

        return response()->json([
            'success' => true,
            'data'    => [
                'user' => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'created_at' => $user->created_at,
                ],
                'wallet' => $wallet ? [
                    'id'        => $wallet->uuid,
                    'balance'   => (float) $wallet->balance,
                    'currency'  => $wallet->currency,
                    'is_active' => $wallet->is_active,
                ] : null,
            ],
        ]);
    }
}
