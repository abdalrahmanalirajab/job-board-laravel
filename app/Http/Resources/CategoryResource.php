<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'jobs_count' => (int) ($this->jobs_count ?? ($this->relationLoaded('jobListings') ? $this->jobListings->where('status', 'approved')->count() : $this->jobListings()->approved()->count())),
        ];
    }
}
