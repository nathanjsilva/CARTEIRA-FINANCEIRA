<?php

namespace App\Infrastructure\Http\Controllers\Api\V1;

use App\Application\DTOs\DepositRequestDTO;
use App\Application\DTOs\WithdrawRequestDTO;
use App\Application\Services\DepositService;
use App\Application\Services\WithdrawService;
use App\Domain\Repositories\TransactionRepository;
use App\Domain\Exceptions\InsufficientFundsException;
use App\Domain\Exceptions\UserNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class WalletController
{
    public function __construct(
        private readonly DepositService $depositService,
        private readonly WithdrawService $withdrawService,
        private readonly TransactionRepository $transactionRepository,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        $user   = $request->user();
        $wallet = $user->getDefaultWallet();

        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Carteira não encontrada'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'wallet_id' => $wallet->id,
                'balance'   => (float) $wallet->balance,
                'currency'  => $wallet->currency,
                'is_active' => $wallet->is_active,
            ],
        ]);
    }

    public function deposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->depositService->execute(
                new DepositRequestDTO(
                    userId: (string) $request->user()->id,
                    amount: (float) $request->input('amount'),
                    description: $request->input('description'),
                )
            );

            $balance = (float) $request->user()->getDefaultWallet()?->balance;

            return response()->json([
                'success' => true,
                'message' => 'Depósito realizado com sucesso',
                'data'    => array_merge($result->toArray(), ['new_balance' => $balance]),
            ], 201);
        } catch (UserNotFoundException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function withdraw(Request $request): JsonResponse
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->withdrawService->execute(
                new WithdrawRequestDTO(
                    userId: (string) $request->user()->id,
                    amount: (float) $request->input('amount'),
                    description: $request->input('description'),
                )
            );

            $balance = (float) $request->user()->fresh()->getDefaultWallet()?->balance;

            return response()->json([
                'success' => true,
                'message' => 'Saque realizado com sucesso',
                'data'    => array_merge($result->toArray(), ['new_balance' => $balance]),
            ], 201);
        } catch (InsufficientFundsException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (UserNotFoundException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $limit  = (int) $request->get('limit', 50);

        $transactions = $this->transactionRepository->findByUserId($userId, $limit);

        return response()->json([
            'success' => true,
            'data'    => [
                'transactions' => array_map(fn ($t) => [
                    'id'         => $t->getId(),
                    'type'       => $t->getType(),
                    'amount'     => $t->getAmount()->getAmount(),
                    'status'     => $t->getStatus(),
                    'created_at' => $t->getCreatedAt()->format('Y-m-d H:i:s'),
                ], $transactions),
            ],
        ]);
    }
}
