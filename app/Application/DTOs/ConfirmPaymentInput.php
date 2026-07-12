<?php

namespace App\Application\DTOs;

class ConfirmPaymentInput
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $employerId,
    ) {}
}
