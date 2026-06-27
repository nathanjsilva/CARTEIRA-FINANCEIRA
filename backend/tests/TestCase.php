<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Create and authenticate a user for testing
     */
    protected function actingAsUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Sanctum::actingAs($user);
        
        return $user;
    }

    /**
     * Create a user with a wallet for testing
     */
    protected function actingAsUserWithWallet(array $userAttributes = [], array $walletAttributes = []): User
    {
        $user = User::factory()->create($userAttributes);

        // UserObserver auto-creates a default wallet; update it with desired attributes
        $user->getDefaultWallet()->update(array_merge([
            'balance' => 1000.00,
        ], $walletAttributes));

        Sanctum::actingAs($user);

        return $user;
    }
}
