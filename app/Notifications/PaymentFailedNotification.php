<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification
{
    public function __construct(
        public readonly int $applicationId,
        public readonly string $jobTitle,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'payment_failed',
            'application_id' => $this->applicationId,
            'job_title'      => $this->jobTitle,
            'message'        => "Payment failed for {$this->jobTitle}",
            'link'           => "/employer/jobs",
            'icon'           => 'exclamation-triangle',
        ];
    }
}
