<?php

namespace App\Infrastructure\Persistence;

use App\Application\DTOs\ApplicationData;
use App\Application\Interfaces\PaymentApplicationInterface;
use App\Models\Application;

class EloquentPaymentApplicationService implements PaymentApplicationInterface
{
    public function getApplication(int $applicationId): ApplicationData
    {
        $application = Application::with('jobListing')->findOrFail($applicationId);

        return new ApplicationData(
            id: $application->id,
            employerId: $application->jobListing->employer_id,
            status: $application->status,
        );
    }

    public function markAsPaid(int $applicationId): void
    {
        Application::where('id', $applicationId)->update(['status' => 'paid']);
    }
}
