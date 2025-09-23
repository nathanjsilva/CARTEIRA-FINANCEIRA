<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class WalletService
{
    public function createWallet(User $user, string $currency = 'BRL'): Wallet
    {
        return DB::transaction(function () use ($user, $currency) {
            $wallet = $user->wallets()->create([
                'balance' => 0.00,
                'currency' => $currency,
                'is_active' => true,
            ]);

            Log::info('Carteira criada', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'currency' => $currency,
            ]);

            return $wallet;
        });
    }

    public function deposit(Wallet $wallet, float $amount, string $description = null): Transaction
    {
        if ($amount <= 0) {
            throw new Exception('Valor deve ser maior que zero');
        }

        return DB::transaction(function () use ($wallet, $amount, $description) {
            // Criar registro de transação
            $transaction = Transaction::create([
                'to_wallet_id' => $wallet->id,
                'type' => 'deposit',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'status' => 'pending',
                'description' => $description,
            ]);

            try {
                // Atualizar saldo da carteira
                $wallet->deposit($amount);
                
                // Marcar transação como concluída
                $transaction->markAsCompleted();

                Log::info('Depósito concluído', [
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                ]);

                return $transaction;
            } catch (Exception $e) {
                $transaction->markAsFailed();
                Log::error('Depósito falhou', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    public function withdraw(Wallet $wallet, float $amount, string $description = null): Transaction
    {
        if ($amount <= 0) {
            throw new Exception('Valor deve ser maior que zero');
        }

        return DB::transaction(function () use ($wallet, $amount, $description) {
            // Criar registro de transação
            $transaction = Transaction::create([
                'from_wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'status' => 'pending',
                'description' => $description,
            ]);

            try {
                // Verificar se a carteira pode sacar
                if (!$wallet->canWithdraw($amount)) {
                    throw new Exception('Saldo insuficiente ou carteira inativa');
                }

                // Atualizar saldo da carteira
                $wallet->withdraw($amount);
                
                // Marcar transação como concluída
                $transaction->markAsCompleted();

                Log::info('Saque concluído', [
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                ]);

                return $transaction;
            } catch (Exception $e) {
                $transaction->markAsFailed();
                Log::error('Saque falhou', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, string $description = null): Transaction
    {
        if ($amount <= 0) {
            throw new Exception('Valor deve ser maior que zero');
        }

        if ($fromWallet->id === $toWallet->id) {
            throw new Exception('Não é possível transferir para a mesma carteira');
        }

        if ($fromWallet->currency !== $toWallet->currency) {
            throw new Exception('Moeda incompatível entre as carteiras');
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description) {
            // Criar registro de transação
            $transaction = Transaction::create([
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'type' => 'transfer',
                'amount' => $amount,
                'currency' => $fromWallet->currency,
                'status' => 'pending',
                'description' => $description,
            ]);

            try {
                // Verificar se a carteira de origem pode sacar
                if (!$fromWallet->canWithdraw($amount)) {
                    throw new Exception('Saldo insuficiente ou carteira de origem inativa');
                }

                // Verificar se a carteira de destino está ativa
                if (!$toWallet->is_active) {
                    throw new Exception('Carteira de destino está inativa');
                }

                // Atualizar saldo das carteiras
                $fromWallet->withdraw($amount);
                $toWallet->deposit($amount);
                
                // Marcar transação como concluída
                $transaction->markAsCompleted();

                Log::info('Transferência concluída', [
                    'transaction_id' => $transaction->id,
                    'from_wallet_id' => $fromWallet->id,
                    'to_wallet_id' => $toWallet->id,
                    'amount' => $amount,
                ]);

                return $transaction;
            } catch (Exception $e) {
                $transaction->markAsFailed();
                Log::error('Transferência falhou', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    public function getBalance(Wallet $wallet): float
    {
        return (float) $wallet->balance;
    }

    public function getTransactionHistory(Wallet $wallet, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where('from_wallet_id', $wallet->id)
            ->orWhere('to_wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
