<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class LargePaymentCompletedNotification extends Notification
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $jobTitle,
        public readonly string $employerName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'large_payment_completed',
            'amount'        => $this->amount,
            'currency'      => $this->currency,
            'job_title'     => $this->jobTitle,
            'employer_name' => $this->employerName,
            'message'       => "Large payment of {$this->currency} {$this->amount} completed by {$this->employerName} for {$this->jobTitle}",
            'link'          => '/admin/jobs',
            'icon'          => 'currency-dollar',
            'priority'      => 'high',
        ];
    }
}
