<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\ApplicationSubmitted;
use App\Models\User;
use App\Notifications\NewApplicationNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendEmployerNewApplicationNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(ApplicationSubmitted $event): void
    {
        try {
            $application = \App\Models\Application::with(['jobListing', 'candidate'])->find($event->applicationId);

            if (!$application) {
                return;
            }

            $employer = User::find($event->employerId);

            if (!$employer) {
                return;
            }

            $this->service->notify($employer, new NewApplicationNotification($application));
        } catch (\Throwable $e) {
            Log::error('Failed to send employer new application notification', [
                'application_id' => $event->applicationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
