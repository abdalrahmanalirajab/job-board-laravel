<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\PaymentCompleted;
use App\Models\Application;
use App\Models\User;
use App\Notifications\LargePaymentCompletedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendAdminLargePaymentNotification
{
    private const LARGE_PAYMENT_THRESHOLD = 500;

    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        try {
            if ($event->amount->amount() < self::LARGE_PAYMENT_THRESHOLD) {
                return;
            }

            $admins = User::where('role', 'admin')->get();

            if ($admins->isEmpty()) {
                return;
            }

            $application = Application::with(['jobListing', 'employer.employerProfile'])->find($event->applicationId);

            if (!$application) {
                return;
            }

            $employerName = optional($application->employer->employerProfile)->company_name
                ?? $application->employer->name;

            $notification = new LargePaymentCompletedNotification(
                $event->amount->amount(),
                $event->amount->currency(),
                $application->jobListing->title,
                $employerName,
            );

            foreach ($admins as $admin) {
                $this->service->notify($admin, $notification);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send admin large payment notification', [
                'payment_id' => $event->paymentId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
