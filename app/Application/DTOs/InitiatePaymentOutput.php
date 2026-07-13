<?php

namespace App\Application\DTOs;

class InitiatePaymentOutput
{
    public function __construct(
        public readonly string $checkoutUrl,
        public readonly int $paymentId,
    ) {}
}
