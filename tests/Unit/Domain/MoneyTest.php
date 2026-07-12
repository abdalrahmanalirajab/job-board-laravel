<?php

namespace Tests\Unit\Domain;

use App\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_create_money(): void
    {
        $money = new Money(50.00);
        $this->assertSame(50.00, $money->amount());
        $this->assertSame('USD', $money->currency());
    }

    public function test_can_create_with_custom_currency(): void
    {
        $money = new Money(100.00, 'EUR');
        $this->assertSame(100.00, $money->amount());
        $this->assertSame('EUR', $money->currency());
    }

    public function test_converts_to_cents(): void
    {
        $money = new Money(50.00);
        $this->assertSame(5000, $money->inCents());
    }

    public function test_converts_fractional_amount_to_cents(): void
    {
        $money = new Money(49.99);
        $this->assertSame(4999, $money->inCents());
    }

    public function test_equality(): void
    {
        $a = new Money(50.00, 'USD');
        $b = new Money(50.00, 'USD');
        $c = new Money(25.00, 'USD');
        $d = new Money(50.00, 'EUR');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
        $this->assertFalse($a->equals($d));
    }

    public function test_immutable(): void
    {
        $money = new Money(50.00);
        $this->assertSame(50.00, $money->amount());
    }

    public function test_rejects_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(-1.00);
    }

    public function test_rejects_empty_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(50.00, '');
    }
}
