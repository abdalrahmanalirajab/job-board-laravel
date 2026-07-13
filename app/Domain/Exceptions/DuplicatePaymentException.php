<?php

namespace App\Domain\Exceptions;

class DuplicatePaymentException extends PaymentException
{
    public function __construct(int $applicationId)
    {
        parent::__construct("A payment already exists for application {$applicationId}");
    }
}
