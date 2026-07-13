<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\PaymentCompleted;
use App\Models\Application;
use App\Models\User;
use App\Notifications\EmployerPaymentCompletedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendEmployerPaymentCompletedNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        try {
            $application = Application::with(['jobListing.employer.employerProfile'])->find($event->applicationId);

            if (!$application) {
                return;
            }

            $employer = User::find($event->employerId);

            if (!$employer) {
                return;
            }

            $this->service->notify($employer, new EmployerPaymentCompletedNotification($application));
        } catch (\Throwable $e) {
            Log::error('Failed to send employer payment completed notification', [
                'application_id' => $event->applicationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
