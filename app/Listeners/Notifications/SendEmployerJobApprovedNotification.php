<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\JobApproved;
use App\Models\User;
use App\Notifications\JobApproved as JobApprovedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendEmployerJobApprovedNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(JobApproved $event): void
    {
        try {
            $employer = User::find($event->employerId);

            if (!$employer) {
                return;
            }

            $job = \App\Models\JobListing::find($event->jobId);

            if (!$job) {
                return;
            }

            $this->service->notify($employer, new JobApprovedNotification($job));
        } catch (\Throwable $e) {
            Log::error('Failed to send employer job approved notification', [
                'job_id'     => $event->jobId,
                'employer_id' => $event->employerId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
