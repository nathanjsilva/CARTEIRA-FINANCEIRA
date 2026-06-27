<?php

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Money;
use App\Domain\Exceptions\InsufficientFundsException;

class UserTest extends TestCase
{
    private function makeUser(string $id, float $balance = 0): User
    {
        return new User($id, "User {$id}", "user{$id}@test.com", 'hashed', Money::of($balance));
    }

    public function test_transfer_with_sufficient_balance(): void
    {
        $sender    = $this->makeUser('1', 1000);
        $recipient = $this->makeUser('2', 0);

        $sender->transfer(Money::of(100), $recipient);

        $this->assertEquals(900.0, $sender->getBalance()->getAmount());
        $this->assertEquals(100.0, $recipient->getBalance()->getAmount());
    }

    public function test_transfer_without_sufficient_balance_throws(): void
    {
        $sender    = $this->makeUser('1', 50);
        $recipient = $this->makeUser('2', 0);

        $this->expectException(InsufficientFundsException::class);
        $sender->transfer(Money::of(100), $recipient);
    }

    public function test_deposit_increases_balance(): void
    {
        $user = $this->makeUser('1', 100);
        $user->deposit(Money::of(50));

        $this->assertEquals(150.0, $user->getBalance()->getAmount());
    }

    public function test_deposit_on_negative_balance_adds_correctly(): void
    {
        $user = new User('1', 'Test', 'test@test.com', 'hash', Money::ofBalance(-30.00));
        $user->deposit(Money::of(100));

        $this->assertEquals(70.0, $user->getBalance()->getAmount());
    }

    public function test_withdraw_decreases_balance(): void
    {
        $user = $this->makeUser('1', 200);
        $user->withdraw(Money::of(50));

        $this->assertEquals(150.0, $user->getBalance()->getAmount());
    }

    public function test_withdraw_without_funds_throws(): void
    {
        $user = $this->makeUser('1', 10);

        $this->expectException(InsufficientFundsException::class);
        $user->withdraw(Money::of(100));
    }

    public function test_can_transfer_returns_true_when_sufficient(): void
    {
        $user = $this->makeUser('1', 500);
        $this->assertTrue($user->canTransfer(Money::of(500)));
    }

    public function test_can_transfer_returns_false_when_insufficient(): void
    {
        $user = $this->makeUser('1', 10);
        $this->assertFalse($user->canTransfer(Money::of(100)));
    }

    public function test_can_transfer_returns_false_when_balance_is_negative(): void
    {
        $user = new User('1', 'Test', 'test@test.com', 'hash', Money::ofBalance(-50.00));
        $this->assertFalse($user->canTransfer(Money::of(10)));
    }

    public function test_register_creates_user_with_zero_balance(): void
    {
        $user = User::register('1', 'John', 'john@test.com', 'hash');
        $this->assertEquals(0.0, $user->getBalance()->getAmount());
    }

    public function test_exact_balance_transfer_leaves_zero(): void
    {
        $sender    = $this->makeUser('1', 100);
        $recipient = $this->makeUser('2', 0);

        $sender->transfer(Money::of(100), $recipient);

        $this->assertEquals(0.0, $sender->getBalance()->getAmount());
    }
}
