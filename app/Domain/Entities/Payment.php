<?php

namespace App\Domain\Entities;

use App\Domain\Enums\PaymentStatus;
use App\Domain\ValueObjects\Money;

class Payment
{
    private PaymentStatus $status;
    private ?\DateTimeImmutable $paidAt;
    private ?string $stripeEventId;

    public function __construct(
        private readonly int $employerId,
        private readonly int $applicationId,
        private readonly Money $amount,
        private readonly string $provider = 'stripe',
        private ?string $stripePaymentIntentId = null,
        private ?string $stripeClientSecret = null,
        private ?string $stripeSessionId = null,
        private ?int $id = null,
    ) {
        $this->status = PaymentStatus::Pending;
        $this->paidAt = null;
        $this->stripeEventId = null;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function employerId(): int
    {
        return $this->employerId;
    }

    public function applicationId(): int
    {
        return $this->applicationId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function stripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(string $id): void
    {
        $this->stripePaymentIntentId = $id;
    }

    public function stripeClientSecret(): ?string
    {
        return $this->stripeClientSecret;
    }

    public function setStripeClientSecret(string $secret): void
    {
        $this->stripeClientSecret = $secret;
    }

    public function stripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(string $sessionId): void
    {
        $this->stripeSessionId = $sessionId;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function paidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function stripeEventId(): ?string
    {
        return $this->stripeEventId;
    }

    public function complete(string $stripeEventId): void
    {
        if ($this->status === PaymentStatus::Completed) {
            return;
        }

        if ($this->status === PaymentStatus::Failed) {
            throw new \RuntimeException('Cannot complete a failed payment');
        }

        $this->status = PaymentStatus::Completed;
        $this->paidAt = new \DateTimeImmutable();
        $this->stripeEventId = $stripeEventId;
    }

    public function fail(): void
    {
        if ($this->status === PaymentStatus::Failed) {
            return;
        }

        if ($this->status === PaymentStatus::Completed) {
            throw new \RuntimeException('Cannot fail a completed payment');
        }

        $this->status = PaymentStatus::Failed;
    }
}
