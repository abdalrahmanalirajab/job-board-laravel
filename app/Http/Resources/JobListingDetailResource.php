<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobListingDetailResource extends JsonResource
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
            'employer_id' => $this->employer_id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'skills_required' => $this->skills_required,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'location' => $this->location,
            'work_type' => $this->work_type,
            'experience_level' => $this->experience_level,
            'status' => $this->status,
            'deadline' => $this->deadline?->toDateString(),
            'logo' => $this->logo,
            'logo_url' => $this->logo ? asset('storage/' . $this->logo) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category'),
            'technologies' => $this->whenLoaded('technologies', function () {
                return $this->technologies->pluck('name');
            }),
            'employer' => $this->whenLoaded('employer'),
            'comments' => $this->whenLoaded('comments'),
        ];
    }
}
