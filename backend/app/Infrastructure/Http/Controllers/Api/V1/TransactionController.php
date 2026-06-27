<?php

namespace App\Infrastructure\Http\Controllers\Api\V1;

use App\Application\DTOs\TransferRequestDTO;
use App\Application\Events\TransactionCompleted;
use App\Application\Events\HighValueTransactionDetected;
use App\Application\Services\TransferService;
use App\Application\Services\Transaction\TransactionReversalService;
use App\Domain\Exceptions\InsufficientFundsException;
use App\Domain\Exceptions\InvalidTransactionException;
use App\Domain\Exceptions\UserNotFoundException;
use App\Infrastructure\Cache\RedisWalletBalanceCache;
use App\Presentation\Http\Requests\TransferRequest;
use App\Presentation\Http\Requests\ReversalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

final class TransactionController
{
    public function __construct(
        private readonly TransferService $transferService,
        private readonly TransactionReversalService $reversalService,
        private readonly RedisWalletBalanceCache $balanceCache,
    ) {}

    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            $senderId = (string) $request->user()->id;
            $result   = $this->transferService->execute(
                new TransferRequestDTO(
                    senderId:    $senderId,
                    recipientId: (string) $request->validated('recipient_id'),
                    amount:      (float) $request->validated('amount'),
                    description: $request->validated('description'),
                )
            );

            $this->balanceCache->invalidate($senderId);
            $this->balanceCache->invalidate($result->recipientId ?? '');

            Event::dispatch(new TransactionCompleted(
                transactionId: $result->id,
                type:          $result->type,
                amount:        $result->amount,
                senderId:      $senderId,
                recipientId:   $result->recipientId ?? '',
            ));

            if ($result->amount >= HighValueTransactionDetected::THRESHOLD) {
                Event::dispatch(new HighValueTransactionDetected($result->id, $result->amount, $result->type, $senderId));
            }

            return response()->json([
                'success' => true,
                'message' => 'Transferência realizada com sucesso',
                'data'    => $result->toArray(),
            ], 201);
        } catch (InsufficientFundsException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (UserNotFoundException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function requestReversal(ReversalRequest $request): JsonResponse
    {
        try {
            $user        = $request->user();
            $transaction = \App\Models\Transaction::where('uuid', $request->validated('transaction_id'))->firstOrFail();

            $userWallet = $user->getDefaultWallet();
            if (!$userWallet ||
                ($transaction->from_wallet_id !== $userWallet->id &&
                    $transaction->to_wallet_id !== $userWallet->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você só pode solicitar reversão para suas próprias transações',
                ], 403);
            }

            $reversal = $this->reversalService->requestReversal(
                $transaction,
                $user,
                $request->validated('reason'),
                $request->validated('description'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Solicitação de reversão enviada com sucesso',
                'data'    => [
                    'reversal_id'             => $reversal->uuid,
                    'original_transaction_id' => $transaction->uuid,
                    'status'                  => $reversal->status,
                    'reason'                  => $reversal->reason,
                ],
            ], 201);
        } catch (InvalidTransactionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function approveReversal(Request $request, string $reversalId): JsonResponse
    {
        try {
            $user     = $request->user();
            $reversal = \App\Models\TransactionReversal::where('uuid', $reversalId)->firstOrFail();

            $this->reversalService->approveReversal($reversal, $user);

            $this->balanceCache->invalidate((string) $reversal->requestedBy?->id);

            return response()->json([
                'success' => true,
                'message' => 'Reversão aprovada e executada com sucesso',
                'data'    => [
                    'reversal_id' => $reversal->uuid,
                    'status'      => $reversal->fresh()->status,
                    'approved_by' => $user->id,
                ],
            ]);
        } catch (InvalidTransactionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function rejectReversal(Request $request, string $reversalId): JsonResponse
    {
        try {
            $reversal = \App\Models\TransactionReversal::where('uuid', $reversalId)->firstOrFail();

            $this->reversalService->rejectReversal($reversal);

            return response()->json([
                'success' => true,
                'message' => 'Reversão rejeitada com sucesso',
                'data'    => ['reversal_id' => $reversal->uuid, 'status' => $reversal->fresh()->status],
            ]);
        } catch (InvalidTransactionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
