<?php

namespace App\Listeners;

use App\Domain\Events\PaymentCompleted;
use App\Models\Application;
use App\Notifications\PaymentCompletedNotification;
use Illuminate\Support\Facades\Log;

class SendPaymentCompletedNotification
{
    public function handle(PaymentCompleted $event): void
    {
        try {
            $application = Application::with('candidate')->find($event->applicationId);

            if ($application === null || $application->candidate === null) {
                Log::warning('Cannot send payment notification: application or candidate not found', [
                    'application_id' => $event->applicationId,
                ]);
                return;
            }

            $application->candidate->notify(new PaymentCompletedNotification($application));
        } catch (\Throwable $e) {
            Log::error('Failed to send payment completed notification', [
                'application_id' => $event->applicationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
