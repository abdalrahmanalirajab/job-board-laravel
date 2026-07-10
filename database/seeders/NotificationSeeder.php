<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\JobListing;
use App\Notifications\ApplicationAccepted;
use App\Notifications\ApplicationRejected;
use App\Notifications\JobApproved;
use App\Notifications\JobRejected;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        // Send ApplicationAccepted notifications for accepted applications
        $acceptedApps = Application::where('status', 'accepted')
            ->with('jobListing.employer.employerProfile')
            ->get();

        foreach ($acceptedApps as $application) {
            if ($application->candidate) {
                $application->candidate->notify(new ApplicationAccepted($application));
            }
        }

        // Send ApplicationRejected for rejected applications
        $rejectedApps = Application::where('status', 'rejected')
            ->with('jobListing.employer.employerProfile')
            ->get();

        foreach ($rejectedApps as $application) {
            if ($application->candidate) {
                $application->candidate->notify(new ApplicationRejected($application));
            }
        }

        // Send JobApproved for approved job listings
        $approvedJobs = JobListing::where('status', 'approved')->with('employer')->get();

        foreach ($approvedJobs as $job) {
            if ($job->employer) {
                $job->employer->notify(new JobApproved($job));
            }
        }

        // Send JobRejected for rejected job listings
        $rejectedJobs = JobListing::where('status', 'rejected')->with('employer')->get();

        foreach ($rejectedJobs as $job) {
            if ($job->employer) {
                $job->employer->notify(new JobRejected($job));
            }
        }
    }
}
