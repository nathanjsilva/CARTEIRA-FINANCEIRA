<?php

namespace App\Infrastructure\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $wallet = $user->getDefaultWallet();
        $token  = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuário registrado com sucesso',
            'data'    => [
                'user'   => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'created_at' => $user->created_at,
                ],
                'wallet' => $wallet ? [
                    'id'        => $wallet->id,
                    'balance'   => (float) $wallet->balance,
                    'currency'  => $wallet->currency,
                    'is_active' => $wallet->is_active,
                ] : null,
                'token'  => $token,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais inválidas',
            ], 401);
        }

        $user   = Auth::user();
        $token  = $user->createToken('auth_token')->plainTextToken;
        $wallet = $user->getDefaultWallet();

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'data'    => [
                'user'   => [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'balance' => $wallet ? (float) $wallet->balance : 0,
                ],
                'token'  => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user   = $request->user();
        $wallet = $user->getDefaultWallet();

        return response()->json([
            'success' => true,
            'data'    => [
                'user'   => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'created_at' => $user->created_at,
                ],
                'wallet' => $wallet ? [
                    'id'        => $wallet->id,
                    'balance'   => (float) $wallet->balance,
                    'currency'  => $wallet->currency,
                    'is_active' => $wallet->is_active,
                ] : null,
            ],
        ]);
    }
}
