<?php

namespace App\Domain\Events;

use App\Domain\ValueObjects\Money;

class PaymentFailed
{
    public function __construct(
        public readonly int $paymentId,
        public readonly int $applicationId,
        public readonly int $employerId,
        public readonly Money $amount,
    ) {}
}
