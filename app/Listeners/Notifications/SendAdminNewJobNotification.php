<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\JobPosted;
use App\Models\User;
use App\Notifications\NewJobPostedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendAdminNewJobNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(JobPosted $event): void
    {
        try {
            $admins = User::where('role', 'admin')->get();
            $job    = \App\Models\JobListing::find($event->jobId);

            if (!$job || $admins->isEmpty()) {
                return;
            }

            foreach ($admins as $admin) {
                $this->service->notify($admin, new NewJobPostedNotification($job));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send admin new job notification', [
                'job_id' => $event->jobId,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
