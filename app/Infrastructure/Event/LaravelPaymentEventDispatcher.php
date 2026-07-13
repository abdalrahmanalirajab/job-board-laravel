<?php

namespace App\Infrastructure\Event;

use App\Application\Interfaces\PaymentEventDispatcherInterface;
use App\Domain\Events\PaymentCompleted;
use App\Domain\Events\PaymentFailed;
use App\Domain\Events\PaymentInitiated;
use Illuminate\Support\Facades\Event;

class LaravelPaymentEventDispatcher implements PaymentEventDispatcherInterface
{
    public function dispatchInitiated(PaymentInitiated $event): void
    {
        Event::dispatch($event);
    }

    public function dispatchCompleted(PaymentCompleted $event): void
    {
        Event::dispatch($event);
    }

    public function dispatchFailed(PaymentFailed $event): void
    {
        Event::dispatch($event);
    }
}
