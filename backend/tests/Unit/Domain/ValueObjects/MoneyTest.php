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
        $this->assertEquals(0, $money->getCents());
    }

    public function test_add_sums_two_amounts(): void
    {
        $result = Money::of(100)->add(Money::of(50));
        $this->assertEquals(150.0, $result->getAmount());
    }

    public function test_subtract_reduces_amount(): void
    {
        $result = Money::of(100)->subtract(Money::of(30));
        $this->assertEquals(70.0, $result->getAmount());
    }

    public function test_precision_float_addition(): void
    {
        $a = Money::of(0.1);
        $b = Money::of(0.2);
        $this->assertEquals(0.3, $a->add($b)->getAmount());
    }

    public function test_from_float_converts_to_cents_correctly(): void
    {
        $money = Money::of(10.99);
        $this->assertEquals(1099, $money->getCents());
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

    public function test_of_balance_allows_negative(): void
    {
        $balance = Money::ofBalance(-50.00);
        $this->assertEquals(-50.0, $balance->getAmount());
        $this->assertTrue($balance->isNegative());
    }

    public function test_deposit_on_negative_balance_adds_correctly(): void
    {
        $balance  = Money::ofBalance(-30.00);
        $deposit  = Money::of(100.00);
        $result   = $balance->add($deposit);
        $this->assertEquals(70.0, $result->getAmount());
    }

    public function test_format_returns_correct_string(): void
    {
        $money = Money::of(1234.56);
        $this->assertEquals('R$ 1.234,56', $money->format());
    }

    public function test_equals_returns_true_for_same_amount(): void
    {
        $this->assertTrue(Money::of(100)->equals(Money::of(100)));
    }

    public function test_equals_returns_false_for_different_amounts(): void
    {
        $this->assertFalse(Money::of(100)->equals(Money::of(200)));
    }
}
