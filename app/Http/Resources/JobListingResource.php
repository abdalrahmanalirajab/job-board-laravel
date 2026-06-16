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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => $this->location,
            'work_type' => $this->work_type,
            'experience_level' => $this->experience_level,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'status' => $this->status,
            'deadline' => $this->deadline?->toDateString(),
            'logo' => $this->logo ? (Str::startsWith($this->logo, ['http://', 'https://']) ? $this->logo : Storage::disk('public')->url($this->logo)) : null,
            'created_at' => $this->created_at,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'technologies' => $this->whenLoaded('technologies', function () {
                return $this->technologies->pluck('name')->toArray();
            }),
            'employer' => $this->whenLoaded('employer', function () {
                $profile = $this->employer->relationLoaded('employerProfile') 
                    ? $this->employer->employerProfile 
                    : ($this->employer->employerProfile()->first() ?? null);

                return [
                    'id' => $this->employer->id,
                    'company_name' => $profile ? $profile->company_name : null,
                    'logo' => $profile && $profile->logo 
                        ? (Str::startsWith($profile->logo, ['http://', 'https://']) ? $profile->logo : Storage::disk('public')->url($profile->logo)) 
                        : null,
                ];
            }),
        ];
    }
}
