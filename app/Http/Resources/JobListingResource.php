<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $logoUrl = null;
        if ($this->logo) {
            $logoUrl = Str::startsWith($this->logo, ['http://', 'https://']) 
                ? $this->logo 
                : asset(Storage::url($this->logo));
        }

        $category = null;
        if ($this->relationLoaded('category') && $this->category) {
            $category = [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ];
        }

        $technologies = [];
        if ($this->relationLoaded('technologies') && $this->technologies) {
            $technologies = $this->technologies->pluck('name')->toArray();
        }

        $employer = null;
        if ($this->relationLoaded('employer') && $this->employer) {
            $profile = $this->employer->employerProfile;
            if (!$profile && !$this->employer->relationLoaded('employerProfile')) {
                $profile = $this->employer->employerProfile()->first();
            }

            $employerLogo = null;
            if ($profile && $profile->logo) {
                $employerLogo = Str::startsWith($profile->logo, ['http://', 'https://'])
                    ? $profile->logo
                    : asset(Storage::url($profile->logo));
            }

            $employer = [
                'id' => $this->employer->id,
                'company_name' => $profile ? $profile->company_name : null,
                'logo' => $employerLogo,
            ];
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => $this->location,
            'work_type' => $this->work_type,
            'experience_level' => $this->experience_level,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'deadline' => $this->deadline?->toDateString(),
            'logo' => $logoUrl,
            'created_at' => $this->created_at,
            'category' => $category,
            'technologies' => $technologies,
            'skills' => $this->skills,
            'applications_count' => (int) ($this->applications_count ?? $this->applications()->count()),
            'employer' => $employer,
        ];
    }
}
