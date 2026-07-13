<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Notification;

class ApplicationConfirmationNotification extends Notification
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
        $companyName = optional($job->employer->employerProfile)->company_name ?? 'the employer';

        return [
            'type'           => 'application_confirmation',
            'application_id' => $this->application->id,
            'job_id'         => $job->id,
            'job_title'      => $job->title,
            'company_name'   => $companyName,
            'message'        => "Your application for {$job->title} at {$companyName} has been submitted",
            'link'           => '/candidate/applications',
            'icon'           => 'check-circle',
        ];
    }
}
