<?php

namespace App\Notifications;

use App\Models\JobListing;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobRejected extends Notification
{

    public function __construct(
        public JobListing $jobListing,
        public ?string $reason = null
    ) {
        $this->jobListing->loadMissing('employer');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your job listing needs revision')
            ->greeting("Hello {$notifiable->name},")
            ->line("Unfortunately, your job listing **\"{$this->jobListing->title}\"** has been rejected by our admin team.");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail
            ->line('Please review the feedback and resubmit your listing after making the necessary changes.')
            ->action('Edit Listing', url('/'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'job_rejected',
            'job_listing_id' => $this->jobListing->id,
            'job_title'      => $this->jobListing->title,
            'reason'         => $this->reason,
            'message'        => "Your job listing '{$this->jobListing->title}' was rejected",
        ];
    }
}
