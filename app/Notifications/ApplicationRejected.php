<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationRejected extends Notification
{
    use Queueable;

    public function __construct(public Application $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $job = $this->application->jobListing;

        return [
            'application_id' => $this->application->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'message' => "Your application for '{$job->title}' has been rejected.",
        ];
    }
}
