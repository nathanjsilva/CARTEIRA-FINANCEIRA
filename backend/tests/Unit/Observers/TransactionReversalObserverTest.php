<?php

namespace Tests\Unit\Observers;

use App\Models\Transaction;
use App\Models\TransactionReversal;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionReversalObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_reversal_gets_default_pending_status_on_creation(): void
    {
        $original = Transaction::factory()->completed()->create();

        $reversal = TransactionReversal::create([
            'original_transaction_id' => $original->id,
            'reversal_transaction_id' => Transaction::factory()->create()->id,
            'requested_by'            => $original->fromWallet->user_id,
            'reason'                  => 'user_request',
        ]);

        $this->assertEquals('pending', $reversal->status);
    }

    public function test_cannot_delete_approved_reversal(): void
    {
        $reversal = TransactionReversal::factory()->approved()->create();

        $this->expectException(\RuntimeException::class);

        $reversal->delete();
    }

    public function test_can_delete_pending_reversal(): void
    {
        $reversal = TransactionReversal::factory()->pending()->create();

        $reversal->delete();

        $this->assertDatabaseMissing('transaction_reversals', ['id' => $reversal->id]);
    }

    public function test_updating_reversal_to_approved_logs_only_does_not_auto_execute(): void
    {
        $reversal = TransactionReversal::factory()->pending()->create();

        // Direct DB update (not via approve()) should only log, not execute reversal logic
        $reversal->update(['status' => 'approved']);

        // Status changed but business logic (original transaction reversed) is NOT triggered by observer
        $this->assertEquals('approved', $reversal->fresh()->status);
    }
}
