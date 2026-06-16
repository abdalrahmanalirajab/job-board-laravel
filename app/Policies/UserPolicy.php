<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{

     // Determine if the user can manage job listings (Employers only).
     
    public function manageJobs(User $user): bool
    {
        return $user->role === 'employer';
    }

      // Determine if the user can apply for jobs or manage profiles (Candidates only).
    
    public function applyForJobs(User $user): bool
    {
        return $user->role === 'candidate';
    }

      // Determine if the user can manage overall platform activity (Admins only).

    public function monitorPlatform(User $user): bool
    {
        return $user->role === 'admin';
    }
}
