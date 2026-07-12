<?php

namespace App\Application\Interfaces;

use App\Application\DTOs\ApplicationData;

interface PaymentApplicationInterface
{
    public function getApplication(int $applicationId): ApplicationData;

    public function markAsPaid(int $applicationId): void;
}
