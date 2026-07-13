<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Notification;

class ApplicationWithdrawnNotification extends Notification
{
    public function __construct(
        public readonly Application $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->application->jobListing;
        $candidate = $this->application->candidate;

        return [
            'type'           => 'application_withdrawn',
            'application_id' => $this->application->id,
            'job_id'         => $job->id,
            'job_title'      => $job->title,
            'candidate_id'   => $candidate->id,
            'candidate_name' => $candidate->name,
            'message'        => "{$candidate->name} withdrew their application for {$job->title}",
            'link'           => "/employer/jobs/{$job->id}/applicants",
            'icon'           => 'x-circle',
        ];
    }
}
