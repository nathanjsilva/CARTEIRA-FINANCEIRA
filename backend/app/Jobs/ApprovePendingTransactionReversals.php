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

    public function handle(TransactionReversalService $reversalService): void
    {
        $systemUser = User::firstOrCreate(
            ['email' => 'system@carteira.finance.local'],
            [
                'name' => 'Sistema de Reversões',
                'password' => Str::random(32),
            ]
        );

        $pendingReversals = TransactionReversal::where('status', 'pending')
            ->with(['originalTransaction', 'reversalTransaction', 'requestedBy'])
            ->get();

        foreach ($pendingReversals as $reversal) {
            try {
                $reversalService->approveReversal($reversal, $systemUser);
            } catch (Throwable $exception) {
                Log::error('Failed to approve pending reversal', [
                    'reversal_id' => $reversal->id,
                    'original_transaction_id' => $reversal->original_transaction_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
