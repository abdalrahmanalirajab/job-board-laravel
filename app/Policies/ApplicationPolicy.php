<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
  /**
   * Determine if a candidate can cancel/delete this application.
   */
  public function delete(User $user, Application $application): bool
  {
    return $user->id === $application->candidate_id && $application->status === 'pending';
  }

  /**
   * Determine if an employer can accept/reject this application.
   */
  public function manage(User $user, Application $application): bool
  {
    return $user->id === $application->jobListing->employer_id;
  }
}