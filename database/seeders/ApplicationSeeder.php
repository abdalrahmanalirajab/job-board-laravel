<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
  public function run(): void
  {
    // Get or create a candidate
    $candidate = User::where('email', 'candidate@jobboard.com')->first();

    if (!$candidate) {
      $candidate = User::create([
        'name' => 'Demo Candidate',
        'email' => 'candidate@jobboard.com',
        'password' => bcrypt('password'),
        'role' => 'candidate',
      ]);
      $candidate->candidateProfile()->create([
        'resume_path' => null,
        'linkedin_url' => 'https://linkedin.com/in/democandidate',
        'bio' => 'A demo candidate looking for exciting opportunities.',
        'skills' => 'PHP, Laravel, Vue.js, MySQL, Git',
      ]);
    }

    // Get approved jobs
    $approvedJobs = JobListing::where('status', 'approved')->get();

    if ($approvedJobs->isEmpty()) {
      return;
    }

    // Create some applications
    $statuses = ['pending', 'pending', 'accepted', 'rejected'];

    foreach ($approvedJobs->take(4) as $index => $job) {
      Application::create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'resume_path' => null,
        'contact_email' => $candidate->email,
        'contact_phone' => '01012345678',
        'status' => $statuses[$index],
        'applied_at' => now()->subDays(rand(1, 10)),
      ]);
    }
  }
}