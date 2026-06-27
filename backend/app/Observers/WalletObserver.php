<?php

namespace App\Observers;

use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WalletObserver
{
    /**
     * Manipula o evento "creating" da Wallet.
     */
    public function creating(Wallet $wallet): void
    {
        Log::info('Carteira sendo criada', [
            'user_id' => $wallet->user_id,
            'currency' => $wallet->currency
        ]);
    }

    /**
     * Manipula o evento "created" da Wallet.
     */
    public function created(Wallet $wallet): void
    {
        Log::info('Carteira criada com sucesso', [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'balance' => $wallet->balance,
            'currency' => $wallet->currency
        ]);

        // Limpar cache de carteiras do usuário
        $this->clearWalletCache($wallet->user_id);
    }

    /**
     * Manipula o evento "updating" da Wallet.
     */
    public function updating(Wallet $wallet): void
    {
        // Armazenar saldo antigo para comparação
        $oldBalance = $wallet->getOriginal('balance');
        $newBalance = $wallet->balance;

        Log::info('Carteira sendo atualizada', [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'balance_change' => $newBalance - $oldBalance,
            'changes' => $wallet->getDirty()
        ]);

        // Validar mudanças de saldo
        if (isset($wallet->getDirty()['balance'])) {
            $this->validateBalanceChange($wallet, $oldBalance, $newBalance);
        }
    }

    /**
     * Manipula o evento "updated" da Wallet.
     */
    public function updated(Wallet $wallet): void
    {
        Log::info('Carteira atualizada com sucesso', [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'updated_fields' => array_keys($wallet->getDirty())
        ]);

        // Limpar cache de carteiras do usuário
        $this->clearWalletCache($wallet->user_id);

        // Verificar atividade suspeita
        $this->checkForSuspiciousActivity($wallet);
    }

    /**
     * Manipula o evento "deleting" da Wallet.
     */
    public function deleting(Wallet $wallet): void
    {
        Log::warning('Carteira sendo excluída', [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'balance' => $wallet->balance
        ]);

        // Impedir exclusão se a carteira tem saldo
        if ($wallet->balance > 0) {
            throw new \Exception('Não é possível excluir carteira com saldo restante');
        }

        // Impedir exclusão se a carteira tem transações ativas
        if ($wallet->sentTransactions()->exists() || $wallet->receivedTransactions()->exists()) {
            throw new \Exception('Não é possível excluir carteira com histórico de transações');
        }
    }

    /**
     * Manipula o evento "deleted" da Wallet.
     */
    public function deleted(Wallet $wallet): void
    {
        Log::warning('Carteira excluída com sucesso', [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id
        ]);

        // Limpar cache de carteiras do usuário
        $this->clearWalletCache($wallet->user_id);
    }

    /**
     * Validar mudanças de saldo
     */
    private function validateBalanceChange(Wallet $wallet, float $oldBalance, float $newBalance): void
    {
        if ($newBalance < 0) {
            Log::warning('Saldo negativo detectado na carteira', [
                'wallet_id' => $wallet->id,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance
            ]);
        }

        $change = abs($newBalance - $oldBalance);
        if ($change > 10000) {
            Log::warning('Mudança grande de saldo detectada', [
                'wallet_id' => $wallet->id,
                'change_amount' => $change,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance
            ]);
        }
    }

    /**
     * Verificar atividade suspeita
     */
    private function checkForSuspiciousActivity(Wallet $wallet): void
    {
        // Verificar mudanças rápidas de saldo
        $recentTransactions = $wallet->sentTransactions()
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentTransactions > 10) {
            Log::warning('Atividade suspeita detectada - alta frequência de transações', [
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'transactions_count' => $recentTransactions
            ]);
        }
    }

    /**
     * Limpar cache de carteiras do usuário
     */
    private function clearWalletCache(int $userId): void
    {
        Cache::forget("user_{$userId}_wallets");
        Cache::forget("user_{$userId}_default_wallet");
    }

}
