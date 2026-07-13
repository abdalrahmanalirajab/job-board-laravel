<?php

namespace Tests\Unit\Domain;

use App\Domain\Enums\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_has_expected_cases(): void
    {
        $this->assertTrue(enum_exists(PaymentStatus::class));
        $this->assertSame('pending', PaymentStatus::Pending->value);
        $this->assertSame('completed', PaymentStatus::Completed->value);
        $this->assertSame('failed', PaymentStatus::Failed->value);
    }

    public function test_is_pending(): void
    {
        $this->assertTrue(PaymentStatus::Pending->isPending());
        $this->assertFalse(PaymentStatus::Completed->isPending());
        $this->assertFalse(PaymentStatus::Failed->isPending());
    }

    public function test_is_completed(): void
    {
        $this->assertTrue(PaymentStatus::Completed->isCompleted());
        $this->assertFalse(PaymentStatus::Pending->isCompleted());
        $this->assertFalse(PaymentStatus::Failed->isCompleted());
    }

    public function test_is_failed(): void
    {
        $this->assertTrue(PaymentStatus::Failed->isFailed());
        $this->assertFalse(PaymentStatus::Pending->isFailed());
        $this->assertFalse(PaymentStatus::Completed->isFailed());
    }
}
