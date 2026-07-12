<?php

namespace App\Domain\Exceptions;

class PaymentException extends \RuntimeException
{
    public function __construct(string $message = 'Payment error', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
