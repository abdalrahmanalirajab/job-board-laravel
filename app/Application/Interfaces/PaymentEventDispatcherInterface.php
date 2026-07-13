<?php

namespace App\Application\Interfaces;

use App\Domain\Events\PaymentCompleted;
use App\Domain\Events\PaymentFailed;
use App\Domain\Events\PaymentInitiated;

interface PaymentEventDispatcherInterface
{
    public function dispatchInitiated(PaymentInitiated $event): void;

    public function dispatchCompleted(PaymentCompleted $event): void;

    public function dispatchFailed(PaymentFailed $event): void;
}
