<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationAccepted extends Notification implements ShouldQueue
{
    use Queueable;

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
        $job         = $this->application->jobListing;
        $companyName = optional($job->employer->employerProfile)->company_name ?? 'the employer';

        return (new MailMessage)
            ->subject('Congratulations! Your application has been accepted')
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! Your application for **{$job->title}** at **{$companyName}** has been accepted.")
            ->line('The employer will be in touch with you soon.')
            ->action('View Application', url('/'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        $job         = $this->application->jobListing;
        $companyName = optional($job->employer->employerProfile)->company_name ?? null;

        return [
            'type'         => 'application_accepted',
            'application_id' => $this->application->id,
            'job_title'    => $job->title,
            'company_name' => $companyName,
            'message'      => "Your application for {$job->title} has been accepted",
        ];
    }
}
