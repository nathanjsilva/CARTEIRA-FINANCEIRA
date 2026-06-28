<?php

namespace App\Infrastructure\Http\Controllers\Api\V1;

use App\Application\DTOs\DepositRequestDTO;
use App\Application\DTOs\WithdrawRequestDTO;
use App\Application\Services\DepositService;
use App\Application\Services\WithdrawService;
use App\Application\Events\TransactionCompleted;
use App\Application\Events\HighValueTransactionDetected;
use App\Domain\Repositories\TransactionRepository;
use App\Domain\Exceptions\InsufficientFundsException;
use App\Domain\Exceptions\UserNotFoundException;
use App\Infrastructure\Cache\RedisWalletBalanceCache;
use App\Presentation\Http\Requests\DepositRequest;
use App\Presentation\Http\Requests\WithdrawRequest;
use App\Presentation\Http\Resources\WalletResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

final class WalletController
{
    public function __construct(
        private readonly DepositService $depositService,
        private readonly WithdrawService $withdrawService,
        private readonly TransactionRepository $transactionRepository,
        private readonly RedisWalletBalanceCache $balanceCache,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        $user   = $request->user();
        $wallet = $user->getDefaultWallet();

        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Carteira não encontrada'], 404);
        }

        $cached = $this->balanceCache->get((string) $user->id);
        $balance = $cached ?? (float) $wallet->balance;

        if ($cached === null) {
            $this->balanceCache->set((string) $user->id, $balance);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'wallet_id' => $wallet->uuid,
                'balance'   => $balance,
                'currency'  => $wallet->currency,
                'is_active' => $wallet->is_active,
            ],
        ]);
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        try {
            $userId = (string) $request->user()->id;
            $result = $this->depositService->execute(
                new DepositRequestDTO(
                    userId:      $userId,
                    amount:      (float) $request->validated('amount'),
                    description: $request->validated('description'),
                )
            );

            $this->balanceCache->invalidate($userId);
            $balance = (float) $request->user()->fresh()->getDefaultWallet()?->balance;

            Event::dispatch(new TransactionCompleted(
                transactionId: $result->id,
                type:          $result->type,
                amount:        $result->amount,
                senderId:      $userId,
                recipientId:   $userId,
            ));

            if ($result->amount >= HighValueTransactionDetected::THRESHOLD) {
                Event::dispatch(new HighValueTransactionDetected($result->id, $result->amount, $result->type, $userId));
            }

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

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        try {
            $userId = (string) $request->user()->id;
            $result = $this->withdrawService->execute(
                new WithdrawRequestDTO(
                    userId:      $userId,
                    amount:      (float) $request->validated('amount'),
                    description: $request->validated('description'),
                )
            );

            $this->balanceCache->invalidate($userId);
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
        $limit  = min((int) $request->get('limit', 50), 100);

        $transactions = $this->transactionRepository->findByUserId($userId, $limit);

        return response()->json([
            'success' => true,
            'data'    => [
                'transactions' => array_map(fn ($t) => [
                    'id'         => $t->getId(),
                    'type'       => $t->getType(),
                    'direction'  => $t->getType() === 'transfer'
                        ? ($t->getSenderId() === $userId ? 'sent' : 'received')
                        : null,
                    'amount'     => $t->getAmount()->getAmount(),
                    'status'     => $t->getStatus(),
                    'created_at' => $t->getCreatedAt()->format('Y-m-d H:i:s'),
                ], $transactions),
            ],
        ]);
    }
}
