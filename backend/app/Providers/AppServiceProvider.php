<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
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
use App\Infrastructure\Cache\RedisWalletBalanceCache;
use App\Application\Services\Auth\RegisterUserService;
use App\Application\Services\Auth\LoginService;
use App\Application\Services\Transaction\TransactionReversalService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(TransactionRepository::class, EloquentTransactionRepository::class);

        $this->app->bind(RedisWalletBalanceCache::class, function ($app) {
            $store = config('cache.default') === 'redis' ? 'redis' : config('cache.default');
            return new RedisWalletBalanceCache(Cache::store($store));
        });

        $this->app->bind(RegisterUserService::class, function ($app) {
            return new RegisterUserService($app->make(UserRepository::class));
        });

        $this->app->bind(LoginService::class, fn () => new LoginService());

        $this->app->bind(TransactionReversalService::class, function ($app) {
            return new TransactionReversalService($app->make(TransactionRepository::class));
        });
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        Wallet::observe(WalletObserver::class);
        Transaction::observe(TransactionObserver::class);
        TransactionReversal::observe(TransactionReversalObserver::class);
    }
}
