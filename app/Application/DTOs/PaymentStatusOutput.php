<?php

namespace App\Application\DTOs;

class PaymentStatusOutput
{
    public function __construct(
        public readonly int $paymentId,
        public readonly string $paymentStatus,
        public readonly string $applicationStatus,
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly ?string $paidAt,
    ) {}
}
