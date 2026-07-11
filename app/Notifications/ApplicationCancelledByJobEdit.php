<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationCancelledByJobEdit extends Notification
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
            ->subject('Your application has been cancelled')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your application for **{$job->title}** at **{$companyName}** has been cancelled because the job listing was updated.")
            ->line('Please review the updated listing and re-apply if you are still interested.')
            ->action('View Job', url('/'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        $job         = $this->application->jobListing;
        $companyName = optional($job->employer->employerProfile)->company_name ?? null;

        return [
            'type'           => 'application_cancelled_by_job_edit',
            'application_id' => $this->application->id,
            'job_listing_id' => $job->id,
            'job_title'      => $job->title,
            'company_name'   => $companyName,
            'message'        => "Your application for {$job->title} was cancelled because the listing was updated",
        ];
    }
}
