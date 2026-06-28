<?php

namespace App\Jobs;

use App\Application\Services\Transaction\TransactionReversalService;
use App\Models\TransactionReversal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ApprovePendingTransactionReversals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const AUTO_APPROVE_REASONS = ['user_request', 'system_error'];
    private const AUTO_REJECT_REASONS  = ['fraud_detection', 'compliance'];

    public function handle(TransactionReversalService $reversalService): void
    {
        $systemUser = User::firstOrCreate(
            ['email' => 'system@carteira.finance.local'],
            [
                'name'     => 'Sistema de Reversões',
                'password' => Str::random(32),
            ]
        );

        $pendingReversals = TransactionReversal::where('status', 'pending')
            ->with(['originalTransaction', 'reversalTransaction', 'requestedBy'])
            ->get();

        foreach ($pendingReversals as $reversal) {
            try {
                if (in_array($reversal->reason, self::AUTO_APPROVE_REASONS)) {
                    $reversalService->approveReversal($reversal, $systemUser);

                    Log::info('Reversão aprovada automaticamente pelo sistema', [
                        'reversal_id' => $reversal->id,
                        'reason'      => $reversal->reason,
                    ]);
                } elseif (in_array($reversal->reason, self::AUTO_REJECT_REASONS)) {
                    $reversalService->rejectReversal($reversal);

                    Log::info('Reversão rejeitada automaticamente pelo sistema', [
                        'reversal_id' => $reversal->id,
                        'reason'      => $reversal->reason,
                    ]);
                }
            } catch (Throwable $exception) {
                Log::error('Falha ao processar reversão pendente', [
                    'reversal_id'             => $reversal->id,
                    'original_transaction_id' => $reversal->original_transaction_id,
                    'reason'                  => $reversal->reason,
                    'error'                   => $exception->getMessage(),
                ]);
            }
        }
    }
}
