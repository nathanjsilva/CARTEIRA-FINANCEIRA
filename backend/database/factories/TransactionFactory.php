<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['deposit', 'withdrawal', 'transfer']);
        $fromWallet = Wallet::factory()->create();
        
        return [
            'from_wallet_id' => $fromWallet->id,
            'to_wallet_id' => $type === 'transfer' ? Wallet::factory()->create()->id : null,
            'type' => $type,
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'currency' => 'BRL',
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'description' => $this->faker->sentence(),
            'metadata' => null,
            'reference_id' => null,
            'processed_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a deposit.
     */
    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deposit',
            'to_wallet_id' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a withdrawal.
     */
    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'withdrawal',
            'to_wallet_id' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'to_wallet_id' => Wallet::factory()->create()->id,
        ]);
    }

    /**
     * Indicate that the transaction is a reversal.
     */
    public function reversal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reversal',
        ]);
    }
}
