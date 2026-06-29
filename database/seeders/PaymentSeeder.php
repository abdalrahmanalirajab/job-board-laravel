<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $acceptedApplications = Application::where('status', 'accepted')
            ->with('jobListing')
            ->get();

        foreach ($acceptedApplications as $index => $application) {
            // 70% completed, 30% pending
            $isCompleted = ($index % 10) < 7;

            $amount = $application->jobListing->salary_max
                ? (float) $application->jobListing->salary_max
                : 50.00;

            Payment::create([
                'employer_id'              => $application->jobListing->employer_id,
                'application_id'           => $application->id,
                'amount'                   => $amount,
                'currency'                 => 'USD',
                'provider'                 => 'stripe',
                'stripe_payment_intent_id' => 'pi_test_' . uniqid(),
                'stripe_client_secret'     => 'pi_test_secret_' . uniqid(),
                'status'                   => $isCompleted ? 'completed' : 'pending',
                'paid_at'                  => $isCompleted ? now() : null,
            ]);
        }
    }
}
