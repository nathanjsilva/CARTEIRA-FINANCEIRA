<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->getDefaultWallet();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Carteira não encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_id' => $wallet->id,
                'balance' => $this->walletService->getBalance($wallet),
                'currency' => $wallet->currency,
                'is_active' => $wallet->is_active,
            ],
        ]);
    }

    public function deposit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erros de validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $wallet = $user->getDefaultWallet();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carteira não encontrada',
                ], 404);
            }

            $transaction = $this->walletService->deposit(
                $wallet,
                $request->amount,
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Depósito realizado com sucesso',
                'data' => [
                    'transaction_id' => $transaction->uuid,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'new_balance' => $this->walletService->getBalance($wallet),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha no depósito',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function withdraw(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erros de validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $wallet = $user->getDefaultWallet();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carteira não encontrada',
                ], 404);
            }

            $transaction = $this->walletService->withdraw(
                $wallet,
                $request->amount,
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Saque realizado com sucesso',
                'data' => [
                    'transaction_id' => $transaction->uuid,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'new_balance' => $this->walletService->getBalance($wallet),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha no saque',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erros de validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $fromWallet = $user->getDefaultWallet();
            $toWallet = Wallet::findOrFail($request->to_wallet_id);

            if (!$fromWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carteira de origem não encontrada',
                ], 404);
            }

            $transaction = $this->walletService->transfer(
                $fromWallet,
                $toWallet,
                $request->amount,
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Transferência realizada com sucesso',
                'data' => [
                    'transaction_id' => $transaction->uuid,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'from_wallet_id' => $fromWallet->id,
                    'to_wallet_id' => $toWallet->id,
                    'new_balance' => $this->walletService->getBalance($fromWallet),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha na transferência',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->getDefaultWallet();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Carteira não encontrada',
            ], 404);
        }

        $limit = $request->get('limit', 50);
        $transactions = $this->walletService->getTransactionHistory($wallet, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_id' => $wallet->id,
                'transactions' => $transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->uuid,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'status' => $transaction->status,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at,
                        'processed_at' => $transaction->processed_at,
                    ];
                }),
            ],
        ]);
    }
}
