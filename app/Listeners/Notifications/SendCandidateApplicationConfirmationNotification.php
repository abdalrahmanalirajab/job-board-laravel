<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\ApplicationSubmitted;
use App\Models\User;
use App\Notifications\ApplicationConfirmationNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendCandidateApplicationConfirmationNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(ApplicationSubmitted $event): void
    {
        try {
            $application = \App\Models\Application::with(['jobListing.employer.employerProfile'])->find($event->applicationId);

            if (!$application) {
                return;
            }

            $candidate = User::find($event->candidateId);

            if (!$candidate) {
                return;
            }

            $this->service->notifyOnce($candidate, new ApplicationConfirmationNotification($application));
        } catch (\Throwable $e) {
            Log::error('Failed to send candidate application confirmation', [
                'application_id' => $event->applicationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
