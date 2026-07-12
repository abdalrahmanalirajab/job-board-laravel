<?php

namespace App\Application\DTOs;

class InitiatePaymentInput
{
    public function __construct(
        public readonly int $applicationId,
        public readonly int $employerId,
        public readonly string $successUrl,
        public readonly string $cancelUrl,
    ) {}
}
