<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPaymentInconsistencies extends Command
{
    protected $signature = 'payments:fix-inconsistencies';

    protected $description = 'Fix orphaned completed payments where application status was not updated';

    public function handle(): int
    {
        $fixed = 0;

        DB::transaction(function () use (&$fixed) {
            Payment::where('status', 'completed')
                ->whereHas('application', fn ($q) => $q->where('status', '!=', 'paid'))
                ->each(function (Payment $payment) use (&$fixed) {
                    $application = $payment->application;
                    if ($application) {
                        $application->update(['status' => 'paid']);
                        $this->info("Fixed: Application {$application->id} → paid (payment {$payment->id})");
                        $fixed++;
                    }
                });
        });

        $backfilled = Payment::where('status', 'completed')
            ->whereNull('stripe_event_id')
            ->update(['stripe_event_id' => DB::raw("'legacy_' || id")]);

        $this->info("Fixed {$fixed} applications, backfilled {$backfilled} stripe_event_ids");

        return Command::SUCCESS;
    }
}
