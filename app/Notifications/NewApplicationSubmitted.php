<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Application $application)
    {
        $this->application->loadMissing('jobListing.employer.employerProfile', 'candidate');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->jobListing;
        $candidateName = $this->application->candidate->name ?? 'A candidate';

        return (new MailMessage)
            ->subject('New application received')
            ->greeting("Hello {$notifiable->name},")
            ->line("You have received a new application from **{$candidateName}** for the **{$job->title}** position.")
            ->line('Please review the application at your earliest convenience.')
            ->action('View Applications', url('/'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->application->jobListing;
        $candidateName = $this->application->candidate->name ?? 'A candidate';

        return [
            'type'           => 'new_application_submitted',
            'application_id' => $this->application->id,
            'job_listing_id' => $job->id,
            'job_title'      => $job->title,
            'candidate_name' => $candidateName,
            'message'        => "New application received for {$job->title} from {$candidateName}",
        ];
    }
}
