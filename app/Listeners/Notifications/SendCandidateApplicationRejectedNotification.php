<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\ApplicationRejected;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationRejected as ApplicationRejectedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendCandidateApplicationRejectedNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(ApplicationRejected $event): void
    {
        try {
            $application = Application::with(['jobListing.employer.employerProfile'])->find($event->applicationId);

            if (!$application) {
                return;
            }

            $candidate = User::find($event->candidateId);

            if (!$candidate) {
                return;
            }

            $this->service->notify($candidate, new ApplicationRejectedNotification($application));
        } catch (\Throwable $e) {
            Log::error('Failed to send candidate application rejected notification', [
                'application_id' => $event->applicationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
