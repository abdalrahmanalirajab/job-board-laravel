<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\ApplicationWithdrawn;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationWithdrawnNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendEmployerApplicationWithdrawnNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(ApplicationWithdrawn $event): void
    {
        try {
            $application = Application::with(['jobListing', 'candidate'])->find($event->applicationId);

            if (!$application) {
                return;
            }

            $employer = User::find($event->employerId);

            if (!$employer) {
                return;
            }

            $this->service->notify($employer, new ApplicationWithdrawnNotification($application));
        } catch (\Throwable $e) {
            Log::error('Failed to send employer application withdrawn notification', [
                'application_id' => $event->applicationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
