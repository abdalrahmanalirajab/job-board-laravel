<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
  public function run(): void
  {
    $employer = User::where('email', 'employer@jobboard.com')->first();
    $candidate = User::where('email', 'candidate@jobboard.com')->first();
    $approvedJobs = JobListing::where('status', 'approved')->get();

    if ($approvedJobs->isEmpty()) {
      return;
    }

    $comments = [
      ['body' => 'Great job opportunity! I have applied.', 'user_id' => $candidate?->id],
      ['body' => 'Looking for the right candidate. Apply now!', 'user_id' => $employer?->id],
      ['body' => 'Is remote work available for this position?', 'user_id' => $candidate?->id],
      ['body' => 'Yes, remote work is fully supported.', 'user_id' => $employer?->id],
    ];

    foreach ($approvedJobs->take(2) as $job) {
      foreach ($comments as $comment) {
        if ($comment['user_id'] === null) {
          continue;
        }
        Comment::create([
          'job_listing_id' => $job->id,
          'user_id' => $comment['user_id'],
          'body' => $comment['body'],
          'is_visible' => true,
        ]);
      }
    }
  }
}