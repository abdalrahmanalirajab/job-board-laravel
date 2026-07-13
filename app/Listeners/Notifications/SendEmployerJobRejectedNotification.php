<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\JobRejected;
use App\Models\User;
use App\Notifications\JobRejected as JobRejectedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendEmployerJobRejectedNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(JobRejected $event): void
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

            $this->service->notify($employer, new JobRejectedNotification($job, $event->reason));
        } catch (\Throwable $e) {
            Log::error('Failed to send employer job rejected notification', [
                'job_id'     => $event->jobId,
                'employer_id' => $event->employerId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
