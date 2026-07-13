<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Notifications\Notification;

class EmployerPaymentCompletedNotification extends Notification
{
    public function __construct(
        public readonly Application $application,
    ) {
        $this->application->loadMissing('jobListing.employer.employerProfile');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->application->jobListing;
        $candidate = $this->application->candidate;
        $companyName = optional($job->employer->employerProfile)->company_name ?? 'your company';

        return [
            'type'           => 'payment_completed',
            'application_id' => $this->application->id,
            'job_id'         => $job->id,
            'job_title'      => $job->title,
            'candidate_name' => $candidate?->name ?? 'the candidate',
            'message'        => "Payment for {$job->title} has been completed successfully",
            'link'           => "/employer/jobs/{$job->id}/applicants",
            'icon'           => 'currency-dollar',
        ];
    }
}
