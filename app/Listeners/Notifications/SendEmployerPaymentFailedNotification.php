<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\PaymentFailed;
use App\Models\Application;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendEmployerPaymentFailedNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(PaymentFailed $event): void
    {
        try {
            $application = Application::with(['jobListing'])->find($event->applicationId);

            if (!$application) {
                return;
            }

            $employer = User::find($event->employerId);

            if (!$employer) {
                return;
            }

            $this->service->notify(
                $employer,
                new PaymentFailedNotification($application->id, $application->jobListing->title)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send employer payment failed notification', [
                'payment_id'    => $event->paymentId,
                'application_id' => $event->applicationId,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
