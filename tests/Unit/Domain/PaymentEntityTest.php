<?php

namespace Tests\Unit\Domain;

use App\Domain\Entities\Payment;
use App\Domain\Enums\PaymentStatus;
use App\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class PaymentEntityTest extends TestCase
{
    private Money $money;

    protected function setUp(): void
    {
        parent::setUp();
        $this->money = new Money(50.00);
    }

    public function test_can_create_pending_payment(): void
    {
        $payment = new Payment(
            employerId: 1,
            applicationId: 1,
            amount: $this->money,
        );

        $this->assertNull($payment->id());
        $this->assertSame(1, $payment->employerId());
        $this->assertSame(1, $payment->applicationId());
        $this->assertTrue($this->money->equals($payment->amount()));
        $this->assertSame('stripe', $payment->provider());
        $this->assertNull($payment->stripePaymentIntentId());
        $this->assertNull($payment->stripeClientSecret());
        $this->assertTrue($payment->status()->isPending());
        $this->assertNull($payment->paidAt());
        $this->assertNull($payment->stripeEventId());
    }

    public function test_can_create_with_stripe_details(): void
    {
        $payment = new Payment(
            employerId: 1,
            applicationId: 1,
            amount: $this->money,
            provider: 'stripe',
            stripePaymentIntentId: 'pi_123',
            stripeClientSecret: 'secret_456',
        );

        $this->assertSame('pi_123', $payment->stripePaymentIntentId());
        $this->assertSame('secret_456', $payment->stripeClientSecret());
    }

    public function test_can_set_id(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->setId(42);
        $this->assertSame(42, $payment->id());
    }

    public function test_can_set_stripe_intent_id(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->setStripePaymentIntentId('pi_new');
        $this->assertSame('pi_new', $payment->stripePaymentIntentId());
    }

    public function test_can_set_stripe_client_secret(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->setStripeClientSecret('secret_new');
        $this->assertSame('secret_new', $payment->stripeClientSecret());
    }

    public function test_can_complete_payment(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->complete('evt_123');

        $this->assertTrue($payment->status()->isCompleted());
        $this->assertNotNull($payment->paidAt());
        $this->assertSame('evt_123', $payment->stripeEventId());
    }

    public function test_completing_already_completed_payment_is_idempotent(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->complete('evt_123');
        $paidAt = $payment->paidAt();

        $payment->complete('evt_456');

        $this->assertTrue($payment->status()->isCompleted());
        $this->assertSame($paidAt, $payment->paidAt());
        $this->assertSame('evt_123', $payment->stripeEventId());
    }

    public function test_cannot_complete_failed_payment(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->fail();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot complete a failed payment');

        $payment->complete('evt_123');
    }

    public function test_can_fail_payment(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->fail();

        $this->assertTrue($payment->status()->isFailed());
    }

    public function test_failing_already_failed_payment_is_idempotent(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->fail();

        $payment->fail();

        $this->assertTrue($payment->status()->isFailed());
    }

    public function test_cannot_fail_completed_payment(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->complete('evt_123');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot fail a completed payment');

        $payment->fail();
    }

    public function test_starts_at_pending(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $this->assertTrue($payment->status()->isPending());
    }

    public function test_completed_payment_has_paid_at_timestamp(): void
    {
        $payment = new Payment(1, 1, $this->money);
        $payment->complete('evt_123');

        $this->assertInstanceOf(\DateTimeImmutable::class, $payment->paidAt());
    }

    public function test_can_use_custom_provider(): void
    {
        $payment = new Payment(1, 1, $this->money, provider: 'manual');
        $this->assertSame('manual', $payment->provider());
    }
}
