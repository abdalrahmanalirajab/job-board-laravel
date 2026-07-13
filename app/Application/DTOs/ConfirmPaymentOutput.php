<?php

namespace App\Application\DTOs;

class ConfirmPaymentOutput
{
    public function __construct(
        public readonly string $status,
        public readonly string $paidAt,
    ) {}
}
