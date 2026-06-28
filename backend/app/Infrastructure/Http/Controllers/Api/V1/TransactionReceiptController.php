<?php

namespace App\Infrastructure\Http\Controllers\Api\V1;

use App\Models\Transaction;
use App\Models\TransactionReversal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TransactionReceiptController
{
    public function download(Request $request, string $uuid): Response
    {
        $user       = $request->user();
        $userWallet = $user->getDefaultWallet();

        $transaction = Transaction::where('uuid', $uuid)
            ->with(['fromWallet.user', 'toWallet.user'])
            ->firstOrFail();

        if (
            ! $userWallet ||
            ($transaction->from_wallet_id !== $userWallet->id &&
             $transaction->to_wallet_id   !== $userWallet->id)
        ) {
            abort(403, 'Acesso não autorizado a esta transação');
        }

        $reversalRecord = null;
        if ($transaction->type === 'reversal') {
            $reversalRecord = TransactionReversal::where('reversal_transaction_id', $transaction->id)
                ->with([
                    'originalTransaction.fromWallet.user',
                    'originalTransaction.toWallet.user',
                    'requestedBy',
                ])
                ->first();
        }

        $reversalInfo = null;
        if ($transaction->type === 'transfer' && $transaction->status === 'reversed') {
            $reversalInfo = TransactionReversal::where('original_transaction_id', $transaction->id)
                ->with(['requestedBy'])
                ->first();
        }

        $typeLabels = [
            'deposit'    => 'Depósito',
            'withdrawal' => 'Saque',
            'transfer'   => 'Transferência',
            'reversal'   => 'Reversão',
        ];

        $statusLabels = [
            'pending'   => 'Pendente',
            'completed' => 'Concluído',
            'failed'    => 'Falhou',
            'reversed'  => 'Revertido',
        ];

        $pdf = Pdf::loadView('pdf.transaction-receipt', [
            'transaction'    => $transaction,
            'reversalRecord' => $reversalRecord,
            'reversalInfo'   => $reversalInfo,
            'currentUser'    => $user,
            'typeLabels'     => $typeLabels,
            'statusLabels'   => $statusLabels,
            'generatedAt'    => now()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s'),
        ])->setPaper('a4');

        return $pdf->download("extrato-{$transaction->uuid}.pdf");
    }
}
