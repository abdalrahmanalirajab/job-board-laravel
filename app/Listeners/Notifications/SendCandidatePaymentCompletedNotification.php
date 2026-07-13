<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\PaymentCompleted;
use App\Models\Application;
use App\Models\User;
use App\Notifications\PaymentCompletedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendCandidatePaymentCompletedNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        try {
            $application = Application::with(['jobListing.employer.employerProfile', 'candidate'])->find($event->applicationId);

            if (!$application || !$application->candidate) {
                return;
            }

            $this->service->notify($application->candidate, new PaymentCompletedNotification($application));
        } catch (\Throwable $e) {
            Log::error('Failed to send candidate payment completed notification', [
                'application_id' => $event->applicationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
