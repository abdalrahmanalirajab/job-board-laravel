<?php

namespace App\Notifications;

use App\Models\JobListing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobApproved extends Notification
{

    public function __construct(public JobListing $jobListing)
    {
        $this->jobListing->loadMissing('employer');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your job listing has been approved!')
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! Your job listing **\"{$this->jobListing->title}\"** has been approved by our admin team.")
            ->line('Your listing is now live and visible to all candidates.')
            ->action('View Listing', url('/'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'job_approved',
            'job_listing_id' => $this->jobListing->id,
            'job_title'     => $this->jobListing->title,
            'message'       => "Your job listing '{$this->jobListing->title}' is now live",
        ];
    }
}
