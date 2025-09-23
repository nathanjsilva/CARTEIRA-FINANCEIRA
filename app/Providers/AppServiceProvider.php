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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        User::observe(UserObserver::class);
        Wallet::observe(WalletObserver::class);
        Transaction::observe(TransactionObserver::class);
        TransactionReversal::observe(TransactionReversalObserver::class);
    }
}
