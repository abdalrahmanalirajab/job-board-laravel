<?php

namespace App\Providers;

use App\Domain\Events\PaymentCompleted;
use App\Listeners\SendPaymentCompletedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentCompleted::class => [
            SendPaymentCompletedNotification::class,
        ],
    ];
}
