<?php

namespace Database\Factories;

use App\Models\TransactionReversal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionReversal>
 */
class TransactionReversalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalTransaction = Transaction::factory()->completed()->create();
        
        return [
            'original_transaction_id' => $originalTransaction->id,
            'reversal_transaction_id' => Transaction::factory()->create()->id,
            'requested_by' => User::factory()->create()->id,
            'reason' => $this->faker->randomElement(['user_request', 'system_error', 'fraud_detection', 'compliance']),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'completed']),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    /**
     * Indicate that the reversal is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the reversal is approved.
     */
    public function approved(): static
    {
        $approver = User::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the reversal is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }
}
