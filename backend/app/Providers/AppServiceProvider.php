<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Observers\UserObserver;
use App\Observers\WalletObserver;
use App\Observers\TransactionObserver;
use App\Observers\TransactionReversalObserver;
use App\Domain\Repositories\UserRepository;
use App\Domain\Repositories\TransactionRepository;
use App\Infrastructure\Repositories\EloquentUserRepository;
use App\Infrastructure\Repositories\EloquentTransactionRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(TransactionRepository::class, EloquentTransactionRepository::class);
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        Wallet::observe(WalletObserver::class);
        Transaction::observe(TransactionObserver::class);
        TransactionReversal::observe(TransactionReversalObserver::class);
    }
}
