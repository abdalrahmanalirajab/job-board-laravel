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
        'skills' => ['PHP', 'Laravel', 'Vue.js', 'MySQL', 'Git'],
      ]);
    }

    $approvedJobs = JobListing::where('status', 'approved')->get();

    if ($approvedJobs->count() < 10) {
      return;
    }

    // Mix of statuses: 5 pending, 3 accepted, 2 rejected
    $statuses = [
      'pending', 'pending', 'pending', 'pending', 'pending',
      'accepted', 'accepted', 'accepted',
      'rejected', 'rejected'
    ];

    foreach ($approvedJobs->take(10) as $index => $job) {
      Application::create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'resume_path' => $index % 2 !== 0 ? 'resumes/dummy_resume.pdf' : null,
        'contact_email' => $index % 2 === 0 ? $candidate->email : null,
        'contact_phone' => '01012345678',
        'status' => $statuses[$index],
        'applied_at' => now()->subDays(rand(1, 10)),
      ]);
    }
  }
}