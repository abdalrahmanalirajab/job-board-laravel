<?php

namespace App\Domain\Exceptions;

class UnauthorizedPaymentException extends PaymentException
{
    public function __construct(string $message = 'You are not authorized to perform this payment action')
    {
        parent::__construct($message);
    }
}
