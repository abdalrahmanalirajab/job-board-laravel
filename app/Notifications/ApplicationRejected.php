<?php

namespace App\Notifications;

use App\Models\Application;
<<<<<<< HEAD
=======
use Illuminate\Bus\Queueable;
>>>>>>> a27b7e7 (fixing bugs)
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationRejected extends Notification
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
        $job         = $this->application->jobListing;
        $companyName = optional($job->employer->employerProfile)->company_name ?? 'the employer';

        return (new MailMessage)
            ->subject('Update on your job application')
            ->greeting("Hello {$notifiable->name},")
            ->line("Thank you for your interest in the **{$job->title}** position at **{$companyName}**.")
            ->line('After careful consideration, we regret to inform you that your application was not selected at this time.')
            ->line('We encourage you to keep applying for other positions.')
            ->action('Browse Jobs', url('/'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        $job         = $this->application->jobListing;
        $companyName = optional($job->employer->employerProfile)->company_name ?? null;

        return [
            'type'           => 'application_rejected',
            'application_id' => $this->application->id,
            'job_title'      => $job->title,
            'company_name'   => $companyName,
            'message'        => "Your application for {$job->title} was not selected",
        ];
    }
}
