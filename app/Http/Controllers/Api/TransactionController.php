<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionReversalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    protected TransactionReversalService $reversalService;

    public function __construct(TransactionReversalService $reversalService)
    {
        $this->reversalService = $reversalService;
    }

    public function requestReversal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,uuid',
            'reason' => 'required|in:user_request,system_error,fraud_detection,compliance',
            'description' => 'nullable|string|max:500',
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
            $transaction = Transaction::where('uuid', $request->transaction_id)->firstOrFail();

            // Verifica se o usuário tem permissão para solicitar reversão
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
                'data' => [
                    'reversal_id' => $reversal->uuid,
                    'original_transaction_id' => $transaction->uuid,
                    'status' => $reversal->status,
                    'reason' => $reversal->reason,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha na solicitação de reversão',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function approveReversal(Request $request, string $reversalId): JsonResponse
    {
        try {
            $user = $request->user();
            $reversal = \App\Models\TransactionReversal::where('uuid', $reversalId)->firstOrFail();

            if (!$reversal->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reversão não está pendente',
                ], 400);
            }

            $this->reversalService->approveReversal($reversal, $user);

            return response()->json([
                'success' => true,
                'message' => 'Reversão aprovada e executada com sucesso',
                'data' => [
                    'reversal_id' => $reversal->uuid,
                    'status' => $reversal->status,
                    'approved_by' => $user->id,
                    'approved_at' => $reversal->approved_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha na aprovação da reversão',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function rejectReversal(Request $request, string $reversalId): JsonResponse
    {
        try {
            $reversal = \App\Models\TransactionReversal::where('uuid', $reversalId)->firstOrFail();

            if (!$reversal->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reversão não está pendente',
                ], 400);
            }

            $this->reversalService->rejectReversal($reversal);

            return response()->json([
                'success' => true,
                'message' => 'Reversão rejeitada com sucesso',
                'data' => [
                    'reversal_id' => $reversal->uuid,
                    'status' => $reversal->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha na rejeição da reversão',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function getPendingReversals(Request $request): JsonResponse
    {
        try {
            $reversals = $this->reversalService->getPendingReversals();

            return response()->json([
                'success' => true,
                'data' => [
                    'reversals' => $reversals->map(function ($reversal) {
                        return [
                            'id' => $reversal->uuid,
                            'original_transaction_id' => $reversal->originalTransaction->uuid,
                            'amount' => $reversal->originalTransaction->amount,
                            'currency' => $reversal->originalTransaction->currency,
                            'reason' => $reversal->reason,
                            'description' => $reversal->description,
                            'requested_by' => $reversal->requestedBy->name,
                            'requested_at' => $reversal->created_at,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao recuperar reversões pendentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getReversalHistory(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $reversals = $this->reversalService->getReversalHistory($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'reversals' => $reversals->map(function ($reversal) {
                        return [
                            'id' => $reversal->uuid,
                            'original_transaction_id' => $reversal->originalTransaction->uuid,
                            'amount' => $reversal->originalTransaction->amount,
                            'currency' => $reversal->originalTransaction->currency,
                            'reason' => $reversal->reason,
                            'description' => $reversal->description,
                            'status' => $reversal->status,
                            'requested_at' => $reversal->created_at,
                            'approved_at' => $reversal->approved_at,
                            'approved_by' => $reversal->approvedBy ? $reversal->approvedBy->name : null,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao recuperar histórico de reversões',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
