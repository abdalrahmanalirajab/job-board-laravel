<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentCompletedNotification extends Notification
{
    public function __construct(public Application $application)
    {
        $this->application->loadMissing('jobListing.employer.employerProfile');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->jobListing;
        $companyName = optional($job->employer->employerProfile)->company_name ?? 'the employer';

        return (new MailMessage)
            ->subject('Payment completed for your application')
            ->greeting("Hello {$notifiable->name},")
            ->line("The employer has completed the payment for your application for **{$job->title}** at **{$companyName}**.")
            ->line('Your application is now marked as paid.')
            ->action('View Application', url('/'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->application->jobListing;
        $companyName = optional($job->employer->employerProfile)->company_name ?? null;

        return [
            'type' => 'payment_completed',
            'application_id' => $this->application->id,
            'job_title' => $job->title,
            'company_name' => $companyName,
            'message' => "Payment completed for your application for {$job->title}",
        ];
    }
}
