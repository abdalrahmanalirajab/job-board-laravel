<?php

namespace App\Notifications;

use App\Models\JobListing;
use Illuminate\Notifications\Notification;

class NewJobPostedNotification extends Notification
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
        $employer = $this->job->employer;
        $companyName = optional($employer->employerProfile)->company_name ?? $employer->name;

        return [
            'type'         => 'new_job_posted',
            'job_id'       => $this->job->id,
            'job_title'    => $this->job->title,
            'employer_id'  => $this->job->employer_id,
            'company_name' => $companyName,
            'message'      => "{$companyName} submitted \"{$this->job->title}\" for review",
            'link'         => '/admin/jobs',
            'icon'         => 'briefcase',
        ];
    }
}
