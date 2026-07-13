<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\JobDeleted;
use App\Models\User;
use App\Notifications\JobDeletedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendEmployerJobDeletedNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(JobDeleted $event): void
    {
        try {
            $employer = User::find($event->employerId);

            if (!$employer) {
                return;
            }

            $job = (object) [
                'id'    => $event->jobId,
                'title' => $event->title,
            ];

            $this->service->notify($employer, new JobDeletedNotification($job));
        } catch (\Throwable $e) {
            Log::error('Failed to send employer job deleted notification', [
                'job_id'     => $event->jobId,
                'employer_id' => $event->employerId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
