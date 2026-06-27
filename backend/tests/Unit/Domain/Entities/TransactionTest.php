<?php

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use App\Domain\Entities\Transaction;
use App\Domain\ValueObjects\Money;
use App\Domain\Exceptions\InvalidTransactionException;

class TransactionTest extends TestCase
{
    public function test_transfer_factory_creates_transfer_type(): void
    {
        $tx = Transaction::transfer('uuid-1', 'sender-1', 'recipient-1', Money::of(100));

        $this->assertEquals('transfer', $tx->getType());
        $this->assertEquals('pending', $tx->getStatus());
        $this->assertEquals(100.0, $tx->getAmount()->getAmount());
    }

    public function test_deposit_factory_creates_deposit_type(): void
    {
        $tx = Transaction::deposit('uuid-1', 'user-1', Money::of(200));

        $this->assertEquals('deposit', $tx->getType());
        $this->assertEquals('user-1', $tx->getSenderId());
        $this->assertEquals('user-1', $tx->getRecipientId());
    }

    public function test_complete_changes_status(): void
    {
        $tx = Transaction::transfer('uuid-1', 'sender-1', 'recipient-1', Money::of(50));
        $tx->complete();

        $this->assertEquals('completed', $tx->getStatus());
        $this->assertTrue($tx->isCompleted());
    }

    public function test_fail_changes_status(): void
    {
        $tx = Transaction::transfer('uuid-1', 'sender-1', 'recipient-1', Money::of(50));
        $tx->fail();

        $this->assertEquals('failed', $tx->getStatus());
    }

    public function test_reverse_changes_status(): void
    {
        $tx = Transaction::transfer('uuid-1', 'sender-1', 'recipient-1', Money::of(50));
        $tx->complete();
        $tx->reverse();

        $this->assertEquals('reversed', $tx->getStatus());
    }

    public function test_invalid_transition_throws_exception(): void
    {
        $tx = Transaction::transfer('uuid-1', 'sender-1', 'recipient-1', Money::of(50));
        $tx->complete();

        $this->expectException(InvalidTransactionException::class);
        $tx->fail();
    }

    public function test_can_be_reversed_only_when_completed_transfer(): void
    {
        $tx = Transaction::transfer('uuid-1', 'sender-1', 'recipient-1', Money::of(50));

        $this->assertFalse($tx->canBeReversed());

        $tx->complete();
        $this->assertTrue($tx->canBeReversed());

        $deposit = Transaction::deposit('uuid-2', 'user-1', Money::of(50));
        $deposit->complete();
        $this->assertFalse($deposit->canBeReversed());
    }
}
