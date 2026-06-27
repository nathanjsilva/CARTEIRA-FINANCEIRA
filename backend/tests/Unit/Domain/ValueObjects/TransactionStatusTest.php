<?php

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObjects\TransactionStatus;
use App\Domain\Exceptions\InvalidTransactionException;

class TransactionStatusTest extends TestCase
{
    public function test_pending_can_transition_to_completed(): void
    {
        $status = TransactionStatus::pending();
        $next   = $status->transitionTo(TransactionStatus::completed());
        $this->assertTrue($next->is('completed'));
    }

    public function test_pending_can_transition_to_failed(): void
    {
        $status = TransactionStatus::pending();
        $next   = $status->transitionTo(TransactionStatus::failed());
        $this->assertTrue($next->is('failed'));
    }

    public function test_completed_can_transition_to_reversed(): void
    {
        $status = TransactionStatus::completed();
        $next   = $status->transitionTo(TransactionStatus::reversed());
        $this->assertTrue($next->is('reversed'));
    }

    public function test_failed_cannot_transition_to_pending(): void
    {
        $this->expectException(InvalidTransactionException::class);
        TransactionStatus::failed()->transitionTo(TransactionStatus::pending());
    }

    public function test_reversed_cannot_transition_to_any(): void
    {
        $this->expectException(InvalidTransactionException::class);
        TransactionStatus::reversed()->transitionTo(TransactionStatus::completed());
    }

    public function test_completed_cannot_transition_to_failed(): void
    {
        $this->expectException(InvalidTransactionException::class);
        TransactionStatus::completed()->transitionTo(TransactionStatus::failed());
    }

    public function test_from_creates_valid_status(): void
    {
        $status = TransactionStatus::from('pending');
        $this->assertTrue($status->is('pending'));
    }

    public function test_from_throws_on_invalid_status(): void
    {
        $this->expectException(\DomainException::class);
        TransactionStatus::from('invalid_status');
    }
}
