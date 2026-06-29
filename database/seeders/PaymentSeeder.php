<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $acceptedApplication = Application::where('status', 'accepted')->first();

        if ($acceptedApplication) {
            Payment::create([
                'employer_id' => $acceptedApplication->jobListing->employer_id,
                'application_id' => $acceptedApplication->id,
                'amount' => 5000,
                'currency' => 'usd',
                'provider' => 'stripe',
                'status' => 'completed',
                'paid_at' => now(),
            ]);
        }
    }
}
