<?php

namespace App\Infrastructure\Http\Controllers\Api\V1;

use App\Application\DTOs\TransferRequestDTO;
use App\Application\Services\TransferService;
use App\Domain\Exceptions\InsufficientFundsException;
use App\Domain\Exceptions\UserNotFoundException;
use App\Services\TransactionReversalService;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class TransactionController
{
    public function __construct(
        private readonly TransferService $transferService,
        private readonly TransactionReversalService $reversalService,
    ) {}

    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'amount'       => 'required|numeric|min:0.01',
            'description'  => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->transferService->execute(
                new TransferRequestDTO(
                    senderId: (string) $request->user()->id,
                    recipientId: (string) $request->input('recipient_id'),
                    amount: (float) $request->input('amount'),
                    description: $request->input('description'),
                )
            );

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

    public function requestReversal(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,uuid',
            'reason'         => 'required|in:user_request,system_error,fraud_detection,compliance',
            'description'    => 'nullable|string|max:500',
        ]);

        try {
            $user        = $request->user();
            $transaction = Transaction::where('uuid', $request->transaction_id)->firstOrFail();

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
                $request->reason,
                $request->description
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
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function approveReversal(Request $request, string $reversalId): JsonResponse
    {
        try {
            $user     = $request->user();
            $reversal = \App\Models\TransactionReversal::where('uuid', $reversalId)->firstOrFail();

            if (!$reversal->isPending()) {
                return response()->json(['success' => false, 'message' => 'Reversão não está pendente'], 400);
            }

            $this->reversalService->approveReversal($reversal, $user);

            return response()->json([
                'success' => true,
                'message' => 'Reversão aprovada e executada com sucesso',
                'data'    => [
                    'reversal_id' => $reversal->uuid,
                    'status'      => $reversal->fresh()->status,
                    'approved_by' => $user->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function rejectReversal(Request $request, string $reversalId): JsonResponse
    {
        try {
            $reversal = \App\Models\TransactionReversal::where('uuid', $reversalId)->firstOrFail();

            if (!$reversal->isPending()) {
                return response()->json(['success' => false, 'message' => 'Reversão não está pendente'], 400);
            }

            $this->reversalService->rejectReversal($reversal);

            return response()->json([
                'success' => true,
                'message' => 'Reversão rejeitada com sucesso',
                'data'    => ['reversal_id' => $reversal->uuid, 'status' => $reversal->fresh()->status],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
