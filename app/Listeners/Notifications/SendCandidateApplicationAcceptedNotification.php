<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\ApplicationAccepted;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationAccepted as ApplicationAcceptedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendCandidateApplicationAcceptedNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(ApplicationAccepted $event): void
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

            $this->service->notify($candidate, new ApplicationAcceptedNotification($application));
        } catch (\Throwable $e) {
            Log::error('Failed to send candidate application accepted notification', [
                'application_id' => $event->applicationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
