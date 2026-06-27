<?php

namespace App\Infrastructure\Cache;

use Illuminate\Contracts\Cache\Repository;

final class RedisWalletBalanceCache
{
    private const TTL = 60;
    private const KEY = 'wallet:balance:';

    public function __construct(private readonly Repository $cache) {}

    public function get(string $userId): ?float
    {
        return $this->cache->get(self::KEY . $userId);
    }

    public function set(string $userId, float $balance): void
    {
        $this->cache->put(self::KEY . $userId, $balance, self::TTL);
    }

    public function invalidate(string $userId): void
    {
        $this->cache->forget(self::KEY . $userId);
    }
}
