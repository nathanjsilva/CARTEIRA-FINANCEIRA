<?php

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObjects\Money;

class MoneyTest extends TestCase
{
    public function test_of_creates_money_with_correct_amount(): void
    {
        $money = Money::of(100.50);
        $this->assertEquals(100.50, $money->getAmount());
    }

    public function test_zero_creates_zero_money(): void
    {
        $money = Money::zero();
        $this->assertEquals(0.0, $money->getAmount());
    }

    public function test_add_sums_two_amounts(): void
    {
        $a      = Money::of(100);
        $b      = Money::of(50);
        $result = $a->add($b);
        $this->assertEquals(150.0, $result->getAmount());
    }

    public function test_subtract_reduces_amount(): void
    {
        $a      = Money::of(100);
        $b      = Money::of(30);
        $result = $a->subtract($b);
        $this->assertEquals(70.0, $result->getAmount());
    }

    public function test_is_greater_or_equal_returns_true_when_equal(): void
    {
        $this->assertTrue(Money::of(100)->isGreaterOrEqual(Money::of(100)));
    }

    public function test_is_greater_or_equal_returns_true_when_greater(): void
    {
        $this->assertTrue(Money::of(200)->isGreaterOrEqual(Money::of(100)));
    }

    public function test_is_greater_or_equal_returns_false_when_less(): void
    {
        $this->assertFalse(Money::of(50)->isGreaterOrEqual(Money::of(100)));
    }

    public function test_negative_amount_throws_exception(): void
    {
        $this->expectException(\DomainException::class);
        Money::of(-1);
    }

    public function test_subtract_resulting_in_negative_throws_exception(): void
    {
        $this->expectException(\DomainException::class);
        Money::of(10)->subtract(Money::of(50));
    }
}
