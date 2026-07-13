<?php

namespace App\Notifications;

use App\Models\JobListing;
use Illuminate\Notifications\Notification;

class JobDeletedNotification extends Notification
{
    public function __construct(
        public readonly JobListing $job,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'job_deleted',
            'job_id'    => $this->job->id,
            'job_title' => $this->job->title,
            'message'   => "Your job listing \"{$this->job->title}\" has been deleted",
            'link'      => '/employer/jobs',
            'icon'      => 'trash',
        ];
    }
}
