<?php

namespace App\Domain\Exceptions;

class InvalidPaymentStateException extends PaymentException
{
    public function __construct(string $message = 'Payment is not in a valid state for this operation')
    {
        parent::__construct($message);
    }
}
