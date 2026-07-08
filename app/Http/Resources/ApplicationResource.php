<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $jobData = $this->whenLoaded('jobListing', function () {
      $job = $this->jobListing;
      if (!$job) return null;

      $category = null;
      if ($job->relationLoaded('category') && $job->category) {
        $category = [
          'id' => $job->category->id,
          'name' => $job->category->name,
        ];
      }

      $employer = null;
      if ($job->relationLoaded('employer') && $job->employer) {
        $profile = $job->employer->employerProfile;
        if (!$profile && !$job->employer->relationLoaded('employerProfile')) {
          $profile = $job->employer->employerProfile()->first();
        }

        $employerLogo = null;
        if ($profile && $profile->logo) {
          $employerLogo = Str::startsWith($profile->logo, ['http://', 'https://'])
            ? $profile->logo
            : Storage::url($profile->logo);
        }

        $employer = [
          'id' => $job->employer->id,
          'company_name' => $profile ? $profile->company_name : null,
          'logo' => $employerLogo,
        ];
      }

      return [
        'id' => $job->id,
        'title' => $job->title,
        'location' => $job->location,
        'work_type' => $job->work_type,
        'category' => $category,
        'employer' => $employer,
      ];
    });

    $candidateData = $this->whenLoaded('candidate', function () {
      $cand = $this->candidate;
      if (!$cand) return null;

      $profile = $cand->candidateProfile;
      if (!$profile && !$cand->relationLoaded('candidateProfile')) {
        $profile = $cand->candidateProfile()->first();
      }

      return [
        'id' => $cand->id,
        'name' => $cand->name,
        'email' => $cand->email,
        'phone' => $this->contact_phone,
        'linkedin' => $profile ? $profile->linkedin_url : null,
        'profile' => [
          'bio' => $profile ? $profile->bio : null,
          'skills' => $profile ? $profile->skills : null,
          'linkedin_url' => $profile ? $profile->linkedin_url : null,
        ],
      ];
    });

    $resumeName = null;
    if ($this->resume_path) {
      $resumeName = 'CV_' . basename($this->resume_path);
    }

    return [
      'id' => $this->id,
      'status' => $this->status,
      'contact_email' => $this->contact_email,
      'contact_phone' => $this->contact_phone,
      'resume_url' => $this->resume_path ? Storage::url($this->resume_path) : null,
      'resume_name' => $resumeName,
      'applied_at' => $this->applied_at ? $this->applied_at->format('Y-m-d H:i:s') : null,
      'created_at' => $this->created_at,
      'rejection_reason' => $this->rejection_reason,
      'job' => $jobData,
      'candidate' => $candidateData,
    ];
  }
}
